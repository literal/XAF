<?php
namespace XAF\web\session;

class MysqlSession extends DbSession
{
	protected $lockTimeoutSec = 10;
	protected $lockRetryUsec = 50000; // 50ms = 0.05 sec

	/**
	 * @param string $token
	 * @return array|bool DB row hash if found, else false
	 */
	protected function dbTryReadAndLock( $token )
	{
		// Quasi-Transaktion durch Table-Locking, d.h. es wird ggf. auf einen anderen Thread gewartet
		// und dann die Session-Tabelle so lange gesperrt, bis der Lese-/Schreib-Komplex abgeschlossen ist
		//
		// @_todo:performance Alternativ könnte man die Locks in eine gesonderte Memory-Tabelle auslagern,
		// sodass dann nur diese gelockt werden muss. Das wäre ähnlich zu den Postgres "advisory locks".
		$this->dbh->exec('LOCK TABLES sessions WRITE');
		$row = $this->dbh->queryRow(
			'SELECT' .
				' id,' .
				' locked,' .
				' request_count,' .
				' UNIX_TIMESTAMP(last_access) AS last_access_ts,' .
				' UNIX_TIMESTAMP() - UNIX_TIMESTAMP(last_access) AS inactive_time,' .
				' data,' .
				' flash_data' .
			' FROM sessions' .
			' WHERE token = ?',
			$token
		);
		if( $row && $row['locked'] == 0 )
		{
			$this->dbh->exec(
				'UPDATE sessions SET locked = 1 WHERE id = ?',
				\intval($row['id'])
			);
		}
		$this->dbh->exec('UNLOCK TABLES');

		if( !$row )
		{
			return null;
		}
		if( $row['locked'] != 0 )
		{
			return false;
		}
		return $row;
	}

	protected function dbCreateAndLock()
	{
		$this->dbh->exec('INSERT INTO sessions(token, last_access, locked) VALUES(?, NOW(), 1)', $this->token);
		$this->id = $this->dbh->getLastInsertId();
	}

	protected function dbWriteAndUnlock()
	{
		$this->dbh->exec(
			'UPDATE sessions SET' .
				' last_access = FROM_UNIXTIME(?),' .
				' request_count = ?' .
				' data = ?,' .
				' flash_data = ?,' .
				' locked = 0' .
			' WHERE id = ?',
			$this->lastAccessTs,
			$this->requestCount,
			$this->serialize($this->data),
			$this->serialize($this->flashDataOut),
			$this->id
		);
	}

	protected function dbDelete()
	{
		$this->dbh->exec('DELETE FROM sessions WHERE id = ?', $this->id);
	}

	protected function dbUnlock()
	{
		$this->dbh->exec('UPDATE sessions SET locked = 0 WHERE id = ?', $this->id);
	}
}
