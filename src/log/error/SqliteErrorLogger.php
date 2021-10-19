<?php
namespace XAF\log\error;

use PDO;
use Exception;

/**
 * Self sufficent error logger that should even work if basic systenm components like the DI container,
 * the DB handler or the config are not present (otherwise it would be impossible to log errors
 * occurred in these components).
 *
 * Logging is done into an SQLite DB so it does not depend on a DB server being operational.
 *
 * As a last resort, if the DB cannot be written to, shortened messages are handed to PHP's own error log
 */
class SqliteErrorLogger implements ErrorLogger
{
	/** @var string application key set for all created log records */
	protected $appKey;

	/** @var string full path to an SQLite DB file */
	protected $sqliteFile;

	/** @var PDO can be null, if DB can not be accessed (causes fallback to internal php error log) */
	protected $pdo;

	/** @var string Name der DB-Tabelle, in die geschrieben wird */
	protected $tableName;

	/** @var int maximum array nesting level for serialized debug data */
	protected $maxDebugNestingDepth;

	/**
	 * @param string $appKey arbitrary application key set for all created log records
	 * @param string $sqliteFile full path to an SQLite DB file
	 * @param string $tableName DB table to be created/written to
	 * @param int $maxDebugNestingDepth
	 */
	public function __construct( $appKey, $sqliteFile, $tableName = 'errorlog', $maxDebugNestingDepth = 8 )
	{
		$this->appKey = $appKey;
		$this->sqliteFile = $sqliteFile;
		$this->tableName = $tableName;
		$this->maxDebugNestingDepth = $maxDebugNestingDepth;
	}

	/**
	 * Schreibt den Log-Eintrag in die DB (bzw. ruft das File-Logging auf, wenn dies nicht erfolgreich ist
	 *
	 * @param string $errorClass
	 * @param string $message
	 * @param array $debugInfo
	 */
	public function logError( $errorClass, $message, $debugInfo = [] )
	{
		$message = $this->forceToUtf8($message);

		$this->openDb();
		if( !$this->pdo )
		{
			$this->logToPhpErrorLog($errorClass, $message);
			return;
		}

		$debugInfoProcessor = new DebugInfoProcessor($this->maxDebugNestingDepth);
		$debugInfoString = $debugInfoProcessor->serializeDebugInfo($debugInfo);
		$debugSerialized = true;

		$requestInfo = $this->getRequestInfo();

		try
		{
			$this->createTableIfNotExists();

			$stmt = $this->pdo->prepare(
				'INSERT INTO ' . $this->tableName . '(' .
					' timestamp,' .
					' appkey,' .
					' request,' .
					' errorclass,' .
					' message,' .
					' remote_address,' .
					' remote_host,' .
					' http_user_agent,' .
					' http_referer,' .
					' user,' .
					' debug_info,' .
					' debug_serialized' .
				') VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
			);
			$stmt->execute([
				\time(),
				$this->appKey,
				$requestInfo['request'],
				$errorClass,
				$message,
				$requestInfo['remote_ip'],
				$requestInfo['remote_host'],
				$requestInfo['user_agent'],
				$requestInfo['referer'],
				$requestInfo['user'],
				$debugInfoString,
				$debugSerialized ? 1 : 0
			]);

			return;
		}
		catch( Exception $e )
		{
			$this->handleDbError($e);
		}
	}

	/**
	 * As error messages sometimes come from sources beyond our control, there may be latin1 encoded
	 * messages...
	 *
	 * @param string $string
	 * @return string
	 */
	protected function forceToUtf8( $string )
	{
		return \mb_check_encoding($string, 'UTF-8') ? $string : \utf8_encode($string);
	}

	protected function openDb()
	{
		if( $this->pdo )
		{
			return;
		}

		$dsn = 'sqlite:' . $this->sqliteFile;
		try
		{
			$this->pdo = new PDO($dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
		}
		catch( Exception $e )
		{
			$this->handleDbError($e);
		}
	}

	protected function handleDbError( Exception $e )
	{
		$this->pdo = null;
		$this->logToPhpErrorLog('errlog', 'failed to access error log db: ' . $e->getMessage());
	}

	/**
	 * @param string $errorClass
	 * @param string $message
	 */
	protected function logToPhpErrorLog( $errorClass, $message )
	{
		\error_log('[' . $this->appKey . ':' . $errorClass . '] ' . $message);
	}

	/**
	 * @return array
	 */
	protected function getRequestInfo()
	{
		if( PHP_SAPI == 'cli' )
		{
			$result = [
				'request' => \implode(' ', $_SERVER['argv']),
				'remote_ip' => null,
				'remote_host' => null,
				'user_agent' => null,
				'referer' => null,
				'user' => $this->getSystemUser()
			];
		}
		else
		{
			$result = [
				'request' => $this->getServerVar('REQUEST_METHOD') . ' ' . $this->getServerVar('REQUEST_URI'),
				'remote_ip' => $this->getServerVar('REMOTE_ADDR'),
				'remote_host' => $this->getRemoteHostName(),
				'user_agent' => $this->getServerVar('HTTP_USER_AGENT'),
				'referer' => $this->getServerVar('HTTP_REFERER'),
				'user' => $this->getServerVar('PHP_AUTH_USER'),
			];
		}

		return $result;
	}

	protected function getServerVar( $key, $default = null )
	{
		return isset($_SERVER[$key]) ? $_SERVER[$key] : $default;
	}

	protected function getRemoteHostName()
	{
		if( isset($_SERVER['REMOTE_HOST']) )
		{
			return $_SERVER['REMOTE_HOST'];
		}
		if( isset($_SERVER['REMOTE_ADDR']) )
		{
			return \gethostbyaddr($_SERVER['REMOTE_ADDR']);
		}
		return null;
	}

	protected function getSystemUser()
	{
		if( !\extension_loaded('posix') )
		{
			return '<system>';
		}

		$userInfo = \posix_getpwuid(\posix_geteuid());
		return $userInfo['name'];
	}

	protected function createTableIfNotExists()
	{
		$this->pdo->exec(
			'CREATE TABLE IF NOT EXISTS ' . $this->tableName .
			'(' .
				' pk INTEGER PRIMARY KEY AUTOINCREMENT,' .
				' timestamp INT NOT NULL,' .
				' appkey TEXT NOT NULL,' .
				' request TEXT,' .
				' errorclass TEXT NOT NULL,' .
				' message TEXT,' .
				' remote_address TEXT,' .
				' remote_host TEXT,' .
				' http_user_agent TEXT,' .
				' http_referer TEXT,' .
				' user TEXT,' .
				' debug_info BLOB,' .
				' debug_serialized INT NOT NULL DEFAULT 0' .
			')'
		);
	}
}
