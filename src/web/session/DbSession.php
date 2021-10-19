<?php
namespace XAF\web\session;

use XAF\exception\SystemError;

use XAF\db\Dbh;
use XAF\event\EventDispatcher;
use XAF\helper\TokenGenerator;

/**
 * @event session.start(Session)
 * @event session.end(Session)
 */
abstract class DbSession implements Session
{
	/** @var Dbh */
	protected $dbh;

	/** @var EventDispatcher */
	protected $eventDispatcher;

	/** @var int */
	protected $sessionTimeoutSec;

	/** @var int initial number of microseconds to sleep before retry when session lock could not be acquired */
	protected $lockRetryUsec = 25000; // 25ms = 0.025 sec

	/** @var int number of times to retry aquisition of a session lock before throwing an exception */
	protected $maxLockRetryCount = 100;

	/** @var int DB-PK in der Session-Tabelle */
	protected $id;

	/** @var string Session-ID */
	protected $token;

	/** @var int Bisherige Anzahl der Zugriffe (HTTP-Requests) */
	protected $requestCount;

	/** @var int Unix Timestamp des letzten Client-Zugriffs */
	protected $lastAccessTs;

	/** @var array Session-Daten, die von anderen Modulen gesetzt/gelesen werden */
	protected $data;

	/** @var array Flash-Session-Daten (Lebenszeit nur von einem Request zum nächsten) - beim Öffnen der Session gelesene Werte */
	protected $flashDataIn;

	/** @var array Flash-Session-Daten (Lebenszeit nur von einem Request zum nächsten) - beim Schließen der Session zu schreibende Werte */
	protected $flashDataOut;

	/** @var bool Ob die Session existiert und noch nicht wieder geschlossen wurde */
	protected $isOpen = false;

	/** @var bool Ob eine neue Session angelegt wurde */
	protected $isNew = false;

	public function __construct( Dbh $dbh, EventDispatcher $eventDispatcher, $sessionTimeoutSec )
	{
		$this->dbh = $dbh;
		$this->eventDispatcher = $eventDispatcher;
		$this->sessionTimeoutSec = $sessionTimeoutSec;
	}

	abstract protected function dbCreateAndLock();

	/**
	 * @param string $token
	 * @return null|bool|array null if not found, false if locking not acquired, a hash of the retrieved DB record otherwiese
	 */
	abstract protected function dbTryReadAndLock( $token );

	abstract protected function dbWriteAndUnlock();

	abstract protected function dbDelete();

	abstract protected function dbUnlock();

	public function start()
	{
		$this->createAndLock();
		$this->eventDispatcher->triggerEvent('session.start', $this);
	}

	protected function createAndLock()
	{
		$this->token = TokenGenerator::generateUrlSafeToken(24);
		$this->dbCreateAndLock();
		$this->reset();
		$this->isNew = true;
		$this->isOpen = true;
	}

	protected function reset()
	{
		$this->data = [];
		$this->flashDataIn = [];
		$this->flashDataOut = [];

		$this->requestCount = 1;
		$this->lastAccessTs = \time();
	}

	/**
	 * @param string $token
	 * @return bool
	 */
	public function continueIfExists( $token )
	{
		if( !$this->openAndLock($token) )
		{
			return false;
		}

		if( $this->isExpired() )
		{
			$this->closeWithoutWriting();
			return false;
		}

		$this->continueSession();
		return true;
	}

	/**
	 * @param string $token alphanumeric session token
	 * @return bool whether the session was found and not locked
	 */
	public function openPassive( $token )
	{
		$dbRow = $this->dbTryReadAndLock($token);
		if( $dbRow )
		{
			$this->openFromDbRow($token, $dbRow);
			return true;
		}

		return false;
	}

	/**
	 * @param string $token
	 * @return bool true if session opened (ID found and session valid)
	 */
	protected function openAndLock( $token )
	{
		$dbRow = $this->readAndLock($token);
		if( $dbRow )
		{
			$this->openFromDbRow($token, $dbRow);
			return true;
		}

		return false;
	}

	/**
	 * @param string $token
	 * @return array|bool a hash of the retrieved DB record if found, otherwise false
	 */
	protected function readAndLock( $token )
	{
		$lockRetryUsec = $this->lockRetryUsec;
		for( $retryCount = 0; $retryCount < $this->maxLockRetryCount; $retryCount++ )
		{
			$row = $this->dbTryReadAndLock($token);
			if( $row === null )
			{
				return false;
			}
			if( $row )
			{
				return $row;
			}
			\usleep($lockRetryUsec);
			// linear backoff
			$lockRetryUsec += $this->lockRetryUsec;
		}

		throw new SystemError('failed to get lock on session', $token);
	}

	/**
	 * @param string $token
	 * @param array $row
	 */
	protected function openFromDbRow( $token, array $row )
	{
		$this->token = $token;
		$this->id = \intval($row['id']);
		$this->requestCount = \intval($row['request_count']);
		$this->lastAccessTs = \intval($row['last_access_ts']);
		$this->data = $this->unserialize($row['data']);
		$this->flashDataIn = $this->unserialize($row['flash_data']);
		$this->flashDataOut = [];
		$this->isOpen = true;
		$this->isNew = false;
	}

	/**
	 * @return bool
	 */
	protected function isExpired()
	{
		return (\time() - $this->lastAccessTs) > $this->sessionTimeoutSec;
	}

	protected function closeWithoutWriting()
	{
		if( $this->isOpen )
		{
			$this->dbUnlock();
			$this->isOpen = false;
		}
	}

	protected function continueSession()
	{
		$this->requestCount++;
		$this->lastAccessTs = \time();
	}

	/**
	 * @return string
	 */
	public function getToken()
	{
		$this->assertOpen();
		return $this->token;
	}

	/**
	 * @return bool
	 */
	public function isOpen()
	{
		return $this->isOpen;
	}

	/**
	 * @return bool
	 */
	public function isNew()
	{
		return $this->isNew;
	}

	/**
	 * @return int
	 */
	public function getRequestCount()
	{
		$this->assertOpen();
		return $this->requestCount;
	}

	/**
	 * @return int
	 */
	public function getLastAccessTs()
	{
		$this->assertOpen();
		return $this->lastAccessTs;
	}

	/**
	 * @param string $key
	 * @param mixed $data
	 */
	public function setData( $key, $data )
	{
		$this->assertOpen();
		$this->data[$key] = $data;
	}

	/**
	 * @param string $key
	 */
	public function unsetData( $key )
	{
		$this->assertOpen();
		unset($this->data[$key]);
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getData( $key )
	{
		$this->assertOpen();
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	/**
	 * Liefert die gesamten Session-Daten - für Fehler-Protokollierung/Debugging
	 *
	 * @return array
	 */
	public function exportData()
	{
		$this->assertOpen();
		return $this->data;
	}

	/**
	 * @param string $key
	 * @param mixed $data
	 */
	public function setFlash( $key, $data )
	{
		$this->assertOpen();
		$this->flashDataOut[$key] = $data;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getFlash( $key )
	{
		$this->assertOpen();
		return isset($this->flashDataIn[$key]) ? $this->flashDataIn[$key] : null;
	}

	public function close()
	{
		if( $this->isOpen )
		{
			$this->dbWriteAndUnlock();
			$this->isOpen = false;
		}
	}

	public function end()
	{
		if( $this->isOpen )
		{
			$this->eventDispatcher->triggerEvent('session.end', $this);
			$this->dbDelete();
			$this->isOpen = false;
		}
	}

	protected function serialize( $data )
	{
		return $data ? \serialize($data) : null;
	}

	protected function unserialize( $data )
	{
		$result = ($data !== null && $data !== '' ? @\unserialize($data) : []);
		if( !\is_array($result) )
		{
			throw new SystemError('invalid session data (not a serialized array)', $data);
		}
		return $result;
	}

	protected function assertOpen()
	{
		if( !$this->isOpen )
		{
			throw new SystemError('attempt to access closed session');
		}
	}
}
