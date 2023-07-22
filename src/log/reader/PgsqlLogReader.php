<?php
namespace XAF\log\reader;

use XAF\db\SqlQuery;
use XAF\db\SqlEscaper;
use DateTime;

class PgsqlLogReader extends SqlLogReader
{
	/**
	 * Postgres is very slow when collecting (a set of relatively few) distinct values from a large table with
	 * 'SELECT DISTINCT x FROM Y' or 'SELECT x FROM Y GROUP BY x'. It always scans all rows instead of using the index
	 * and moving on to the next value when the first occurrence of a particular value is found.
	 *
	 * So unless we need the counts for sorting, we emulate the desired behaviour with a recursive CTE.
	 *
	 * Make sure all filter value columns have indexes or this will be even slower than native distinct!
	 *
	 * @param string $fieldName
	 * @param string $orderBy Any of the self::SORT_* constants
	 */
	public function getFilterValues( $fieldName, $orderBy = self::SORT_VALUE )
	{
		if( $orderBy != self::SORT_VALUE )
		{
			return parent::getFilterValues($fieldName, $orderBy);
		}

		$fieldSqlExpression = $this->schema->getFieldSqlExpression($fieldName);
		return $this->dbh->queryColumn(
			'WITH RECURSIVE distinct_values(currval) AS ('
					// Initially get the smallest filter value
					. ' SELECT MIN(' . $fieldSqlExpression . ') FROM ' . $this->tableName
				. ' UNION'
					// Then iterate over all other values, each time taking the smallest value that is greater
					// than the previous one.
					. ' SELECT ('
						. 'SELECT MIN(' . $fieldSqlExpression . ')'
						. ' FROM ' . $this->tableName
						. ' WHERE ' . $fieldSqlExpression . ' > currval'
					. ')'
				. ' FROM distinct_values'
					// Iteration terminates when NULL is returned
			. ')'
			// Must eliminate the final NULL value (or the initial one if there are no values at all)
			. 'SELECT currval FROM distinct_values WHERE currval IS NOT NULL'
		);
	}

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
		return  $valueExpression . ' ILIKE :' . $placeholderName;
	}

	/**
	 * @param DateTime $dt
	 * @return string
	 */
	protected function dateTimeToDbValue( DateTime $dt )
	{
		return $dt->format('Y-m-d H:i:sO');
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
