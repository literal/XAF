<?php
namespace XAF\web\session;

class MysqlSessionGarbageCollector extends DbSessionGarbageCollector
{
	protected function getExpiredSessionsTokens()
	{
		return $this->dbh->queryColumn(
			'SELECT token FROM sessions WHERE UNIX_TIMESTAMP() - UNIX_TIMESTAMP(last_access) > ?',
			$this->sessionTimeoutSec
		);
	}
}
