<?php
namespace XAF\log\session;

use XAF\db\Dbh;
use XAF\web\session\Session;

class PgsqlSessionLogger
{
	/** @var Dbh */
	private $dbh;

	/**
	 * @param Dbh $dbh
	 */
	public function __construct( Dbh $dbh )
	{
		$this->dbh = $dbh;
	}

	/**
	 * Legt den (unvollständigen) Log-Eintrag direkt nach Erzeugung einer Session an - der
	 * Eintrag wird nach Ablauf der Session komplettiert (Dauer, Anzahl der Zugriffe)
	 *
	 * Event handler for session.start
	 *
	 * @param Session $session
	 */
	public function logSessionCreation( Session $session )
	{
		$this->dbh->exec(
			'INSERT INTO log_sessions(' .
				' session_token,' .
				' site,' .
				' created,' .
				' remote_ip,' .
				' remote_host,' .
				' user_agent,' .
				' referer' .
			') VALUES(?, ?, CURRENT_TIMESTAMP, ?, ?, ?, ?)',
			$session->getToken(),
			$_SERVER['SERVER_NAME'],
			$_SERVER['REMOTE_ADDR'],
			isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : \gethostbyaddr($_SERVER['REMOTE_ADDR']),
			isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
			isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null
		);
	}

	/**
	 * Ergänzt, nach dem Login eines Users, den Log-Eintrag der Session um den Usernamen
	 *
	 * @param int $userPk
	 * @param string $userName
	 * @param Session $sessionToken
	 */
	public function logLogin( $userPk, $userName, $sessionToken )
	{
		$this->dbh->exec(
			'UPDATE log_sessions SET user_name = ? WHERE session_token = ?',
			$userName, $sessionToken
		);
	}

	/**
	 * Finalize the log entry for a terminated session
	 *
	 * Event handler for 'session.end'
	 *
	 * @param Session $session
	 */
	public function finalizeLogEntry( Session $session )
	{
		$this->dbh->exec(
			'UPDATE log_sessions SET' .
				' duration_sec = ? - EXTRACT(EPOCH FROM created),' .
				' request_count = ?' .
			' WHERE session_token = ?',
			$session->getLastAccessTs(),
			$session->getRequestCount(),
			$session->getToken()
		);
	}
}
