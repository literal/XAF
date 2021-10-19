<?php
namespace XAF\web\session;

use XAF\db\Dbh;

abstract class DbSessionGarbageCollector implements SessionGarbageCollector
{
	/** @var Session */
	protected $session;

	/** @var Dbh */
	protected $dbh;

	/** @var int */
	protected $sessionTimeoutSec;

	/**
	 * @param Session $session A virgin session object which will be re-used for every single session to be killed
	 * @param Dbh $dbh
	 * @param int $sessionTimeoutSec 
	 */
	public function __construct( Session $session, Dbh $dbh, $sessionTimeoutSec )
	{
		$this->session = $session;
		$this->dbh = $dbh;
		$this->sessionTimeoutSec = $sessionTimeoutSec;
	}

	/**
	 * Delete all expired sessions
	 *
	 * @return int
	 */
	public function cleanup()
	{
		$killCount = 0;
		foreach( $this->getExpiredSessionsTokens() as $sessionToken )
		{
			if( $this->endSession($sessionToken) )
			{
				$killCount++;
			}
		}
		return $killCount;
	}

	/**
	 * @return array a scalar array of session tokens
	 */
	abstract protected function getExpiredSessionsTokens();

	/**
	 * Delete a session
	 *
	 * @param string $token
	 * @return bool Whether successful
	 */
	protected function endSession( $token )
	{
		if( $this->session->openPassive($token) )
		{
			$this->session->end();
			return true;
		}
		return false;
	}
}
