<?php
namespace XAF\db;

use XAF\exception\NotFoundError;

/**
 * Base class for preparing and executing an SQL query and processing the raw DB results.
 *
 * To implement a custom query,
 *
 * you have to implement:
 * - A public method for running the query and returning the result, typically execute(), which completes
 *   the SQL query and calls any of the run*Query() methods
 *
 * you should override:
 * - init() to set the source table name on the SqlQuery instance and all other stuff that
 *   is identical for all executions of the query.
 *
 * you might want to override:
 * - handleRowNotFound() to e.g. throw a custom exception when a single row query returned no result
 * - processRow() to do custom post-processing on the raw DB rows
 */
abstract class DbQuery
{
	/** @var Dbh */
	protected $dbh;

	/** @var SqlQuery */
	protected $sqlQuery;

	public function __construct( Dbh $dbh )
	{
		$this->dbh = $dbh;
		$this->sqlQuery = new SqlQuery();
		$this->init();
	}

	protected function init()
	{
	}

	/**
	 * @return array
	 */
	protected function queryAllRows()
	{
		$rows = $this->dbh->queryTable($this->sqlQuery->getSqlStatement(), $this->sqlQuery->getParams());
		return \array_map([$this, 'processRow'], $rows);
	}

	/**
	 * @return array
	 */
	protected function querySingleRow()
	{
		$row = $this->dbh->queryRow($this->sqlQuery->getSqlStatement(), $this->sqlQuery->getParams());
		if( !$row )
		{
			$this->handleRowNotFound();
		}
		return $this->processRow($row);
	}

	protected function handleRowNotFound()
	{
		throw new NotFoundError('database row', $this->sqlQuery);
	}

	protected function processRow( array $row )
	{
		return $row;
	}
}
