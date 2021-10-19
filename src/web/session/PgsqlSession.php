<?php
namespace XAF\web\session;

use PDO;
use XAF\db\Dbh;

class PgsqlSession extends DbSession
{
	/**
	 * @param string $token
	 * @return null|bool|array null if not found, false if locking not acquired, a hash of the retrieved DB record otherwiese
	 */
	protected function dbTryReadAndLock( $token )
	{
		// Just using pg_advisory_lock() here is *not* appropriate, it would make the query block until the
		// lock is freed by another process but it would then return the *old* session data, that was
		// read when the query started! I. e. it would not get the updated session state written by the
		// other process before releasing the lock.

		$row = $this->dbh->queryRow(
			'SELECT' .
				' pg_try_advisory_lock(id) AS lock_acquired,' . // try obtaining cooperative row lock (boolean result)
				' id,' .
				' request_count,' .
				' EXTRACT(EPOCH FROM last_access) AS last_access_ts,' .
				' data,' .
				' flash_data' .
			' FROM sessions' .
			' WHERE token = ?',
			$token
		);
		if( !$row )
		{
			return null;
		}
		if( !$row['lock_acquired'] )
		{
			return false;
		}
		$row['data'] = Dbh::unwrapBlobContents($row['data']);
		$row['flash_data'] = Dbh::unwrapBlobContents($row['flash_data']);
		return $row;
	}

	protected function dbCreateAndLock()
	{
		$this->dbh->exec('INSERT INTO sessions(token, last_access) VALUES(?, CURRENT_TIMESTAMP)', $this->token);
		$this->id = $this->dbh->getLastInsertId('sessions_id_seq');
		$this->dbLock();
	}

	protected function dbWriteAndUnlock()
	{
		$stmt = $this->dbh->prepare(
			'UPDATE sessions SET' .
				' last_access = to_timestamp(?),' .
				' request_count = ?,' .
				' data = ?,' .
				' flash_data = ?' .
			' WHERE id = ?'
		);
		// This is where Postgres sucks: BLOBs aka BYTEAs are passed in a crazy double-backslash
		// escaped format, fortunately PDO handles this for explicit PDO::PARAM_LOB arguments
		$stmt->bindValue(1, $this->lastAccessTs, PDO::PARAM_INT);
		$stmt->bindValue(2, $this->requestCount, PDO::PARAM_INT);
		$stmt->bindValue(3, $this->serialize($this->data), PDO::PARAM_LOB);
		$stmt->bindValue(4, $this->serialize($this->flashDataOut), PDO::PARAM_LOB);
		$stmt->bindValue(5, $this->id, PDO::PARAM_INT);
		$stmt->execute();

		$this->dbUnlock();
	}

	protected function dbDelete()
	{
		$this->dbh->exec('DELETE FROM sessions WHERE id = ?', $this->id);
		$this->dbUnlock();
	}

	protected function dbLock()
	{
		$this->dbh->exec('SELECT pg_advisory_lock(?)', $this->id);
	}

	protected function dbUnlock()
	{
		$this->dbh->exec('SELECT pg_advisory_unlock(?)', $this->id);
	}
}
