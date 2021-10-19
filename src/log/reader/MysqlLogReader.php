<?php
namespace XAF\log\reader;

use XAF\db\SqlQuery;
use XAF\db\SqlEscaper;
use DateTime;

class MysqlLogReader extends SqlLogReader
{
	/**
	 * @param string $valueExpression
	 * @param string $alias
	 * @return string
	 */
	protected function buildFieldSelectTerm( $valueExpression, $alias )
	{
		return $valueExpression . ' AS "' . $alias . '"';
	}

	/**
	 * @param string $searchPhrase
	 * @return string
	 */
	protected function buildSearchPatternValue( $searchPhrase )
	{
		return '%' . SqlEscaper::likeEscape($searchPhrase) . '%';
	}

	/**
	 * @param string $valueExpression
	 * @param string $placeholderName
	 * @return string
	 */
	protected function buildSqlSearchTerm( $valueExpression, $placeholderName )
	{
		return  $valueExpression . ' LIKE :' . $placeholderName;
	}

	/**
	 * @param DateTime $dt
	 * @return string
	 */
	protected function dateTimeToDbValue( DateTime $dt )
	{
		return $dt->format('Y-m-d H:i:s');
	}

	/**
	 * @param mixed $dbValue
	 * @return DateTime|null
	 */
	protected function dbToDateTime( $dbValue )
	{
		return isset($dbValue) ? new DateTime($dbValue) : null;
	}
}
