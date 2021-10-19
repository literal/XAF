<?php
namespace XAF\web\session;

class PgsqlSessionGarbageCollector extends DbSessionGarbageCollector
{
	protected function getExpiredSessionsTokens()
	{
		return $this->dbh->queryColumn(
			'SELECT token FROM sessions WHERE EXTRACT(EPOCH FROM CURRENT_TIMESTAMP - last_access) > ?',
			$this->sessionTimeoutSec
		);
	}
}
