<?php
namespace XAF\log\reader;

use XAF\db\Dbh;
use XAF\db\SqlQuery;

use DateTime;
use XAF\type\TimeRange;
use XAF\type\ListPageRequest;
use XAF\type\PageInfo;

/**
 * Generic reader for SQL-RDBMS based logs
 */
abstract class SqlLogReader
{
	const SORT_VALUE = 'value';
	const SORT_COUNT = 'count';

	/** @var Dbh */
	protected $dbh;

	/** @var Schema */
	protected $schema;

	/** @var string */
	protected $tableName;

	/**
	 * @param Dbh $dbh
	 * @param Schema $schema
	 * @param string $tableName
	 */
	public function __construct( Dbh $dbh, Schema $schema, $tableName )
	{
		$this->dbh = $dbh;
		$this->schema = $schema;
		$this->tableName = $tableName;
	}

	/**
	 * @param string $fieldName
	 * @param string $orderBy Any of the self::SORT_* constants
	 */
	public function getFilterValues( $fieldName, $orderBy = self::SORT_VALUE )
	{
		$fieldSqlExpression = $this->schema->getFieldSqlExpression($fieldName);
		$orderByExpression = $orderBy == self::SORT_VALUE
			? $this->schema->getFieldOrderByExpression($fieldName)
			: 'COUNT(*) DESC';
		return $this->dbh->queryColumn(
			'SELECT ' . $fieldSqlExpression
			. ' FROM ' . $this->tableName
			. ' GROUP BY ' . $fieldSqlExpression
			. ' ORDER BY ' . $orderByExpression
		);
	}

	/**
	 * Filters can be "timeRange" (a TimeRange object), "search" (applied to multiple fields according to schema)
	 * and any field name with a single value or an array of values to filter by, where each value may be prefixed
	 * with "!" to select only records not having that value.
	 *
	 * @return array [entries: <array>, pageInfo: <PageInfo>]
	 */
	public function getLogPage( ListPageRequest $request )
	{
		$query = $this->buildLogPageQuery($request);

		list($rows, $totalCount) = $this->executeLogPageQuery($query);

		$pageInfo = new PageInfo($request->pageNumber, $request->pageSize);
		$pageInfo->setTotalItemCount($totalCount);

		return ['entries' => $this->processLogRows($rows), 'pageInfo' => $pageInfo];
	}

	/**
	 * @param ListPageRequest $request
	 * @return SqlQuery
	 */
	protected function buildLogPageQuery( ListPageRequest $request )
	{
		$query = new SqlQuery($this->tableName);
		foreach( $this->schema->getAllFieldsSqlExpressions() as $fieldName => $sqlExpression )
		{
			$query->addFieldTerm($this->buildFieldSelectTerm($sqlExpression, $fieldName));
		}
		$this->applyFilters($request->filters, $query);
		$query->addOrderByTerm($this->schema->getGlobalOrderBySqlExpression());

		if( $request->pageSize > 0 )
		{
			$query->setLimit($request->pageSize, \max(0, $request->pageNumber - 1) * $request->pageSize);
		}

		return $query;
	}

	/**
	 * @param SqlQuery $query
	 * @return [<array rows>, <int totalCount>]
	 */
	protected function executeLogPageQuery( SqlQuery $query )
	{
		return [
			$this->dbh->queryTable($query->getSqlStatement(), $query->getParams()),
			$this->queryTotalItemCount($query)
		];
	}

	/**
	 * @param SqlQuery $query
	 * @return int
	 */
	protected function queryTotalItemCount( SqlQuery $query )
	{
		$countQuery = clone $query;
		$countQuery->clearFields();
		$countQuery->addFieldTerm('COUNT(*)');
		$countQuery->setLimit(null);
		$countQuery->clearOrderBy(null);
		return \intval($this->dbh->queryValue($countQuery->getSqlStatement(), $countQuery->getParams()));
	}


	/**
	 * @param array $rows
	 * @return array
	 */
	protected function processLogRows( array $rows )
	{
		$conversionsByField = $this->schema->getFieldValueConversionKeys();

		foreach( $rows as &$row )
		{
			foreach( $conversionsByField as $fieldName => $conversion )
			{
				$row[$fieldName] = $this->convertResultValue($row, $fieldName, $conversion);
			}
		}
		return $rows;
	}

	/**
	 * @param array $row
	 * @param string $fieldName
	 * @param callable|string $conversion
	 * @return mixed
	 */
	protected function convertResultValue( array $row, $fieldName, $conversion )
	{
		$value = $row[$fieldName];

		if( \is_callable($conversion) )
		{
			return \call_user_func($conversion, $value, $row);
		}

		switch( $conversion )
		{
			case 'timestamp':
				return $this->dbToDateTime($value);
		}

		return $value;
	}

	/**
	 * @param array $splitByFields Fields to split by
	 * @param array $filters Filters can be "timeRange" (a TimeRange object), "search" (applied to multiple
	 *     fields according to schema) and any field name with a single value or an array of values to filter by,
	 *     where values may be prefixed with "!" to select only records not having that value.
	 * @return array {<split field 1>: {<split field 2>: {<split field 3>: <count>, ...}, ...}, ...}
	 *     Nesting depth depends on number of split by fields passed in,
	 *     All keys are once null to mark respective totals (i.e. the rollup)
	 */
	public function getSummary( array $splitByFields, array $filters = [] )
	{
		$query = new SqlQuery($this->tableName);
		$query->addFieldTerm('COUNT(*) AS count');
		$hasAscendingOrderByFieldName = [];
		foreach( $splitByFields as $fieldName )
		{
			$query->addFieldTerm($this->schema->getFieldSqlExpression($fieldName) . ' AS "' . $fieldName . '"');
			$query->addGroupByTerm('"' . $fieldName . '"');
			$hasAscendingOrderByFieldName[$fieldName] = $this->schema->isAscendingOrderField($fieldName);
		}
		$this->applyFilters($filters, $query);

		$rows = $this->dbh->queryTable($query->getSqlStatement(), $query->getParams());
		return StatsRollupBuilder::transform($rows, $splitByFields, 'count', $hasAscendingOrderByFieldName);
	}

	private function applyFilters( array $filters, SqlQuery $query )
	{
		if( isset($filters['timeRange']) )
		{
			$this->applyTimeRangeFilter($filters['timeRange'], $query);
		}

		foreach( $this->schema->getSearchAndLookupFieldSqlExpressionsBySearchPhraseKey() as $searchPhraseKey => $expressionsByMatchType )
		{
			if( isset($filters[$searchPhraseKey]) )
			{
				$this->applySearchFilter(
					$searchPhraseKey,
					$filters[$searchPhraseKey],
                    $expressionsByMatchType['search'] ?? [],
                    $expressionsByMatchType['lookup'] ?? [],
					$query
				);
			}
		}

		foreach( $filters as $fieldName => $values )
		{
			if( $this->schema->doesFieldExist($fieldName) && $values !== null )
			{
				$this->applyValueFilter($fieldName, (array)$values, $query);
			}
		}
	}

	private function applyTimeRangeFilter( TimeRange $timeRange, SqlQuery $query )
	{
		$eventTimeSqlExpression = $this->schema->getEventTimeSqlExpression();
		$startDate = $timeRange->getStart();
		if( $startDate !== null )
		{
			$query->addWhereTerm(
				$eventTimeSqlExpression . ' >= :startDate',
				['startDate' => $this->dateTimeToDbValue($startDate)]
			);
		}
		$endDate = $timeRange->getEnd();
		if( $endDate !== null )
		{
			$query->addWhereTerm(
				$eventTimeSqlExpression . ' <= :endDate',
				['endDate' => $this->dateTimeToDbValue($endDate)]
			);
		}
	}

	/**
	 * @param string $fieldName
	 * @param array $values
	 * @param SqlQuery $query
	 */
	private function applyValueFilter( $fieldName, array $values, SqlQuery $query )
	{
		$positivePlaceholders = [];
		$negativePlaceholders = [];
        $isNullValuePresent = false;
		$paramsValues = [];

		$index = 1;
		foreach( $values as $filterValue )
		{
			$paramKey = $fieldName . $index;

            if( $filterValue === null )
            {
                $isNullValuePresent = true;
            }
			else if( \strlen($filterValue) > 0 && $filterValue[0] === '!' )
			{
				$negativePlaceholders[] = ':' . $paramKey;
				$paramsValues[$paramKey] = \substr($filterValue, 1);
			}
			else
			{
				$positivePlaceholders[] = ':' . $paramKey;
				$paramsValues[$paramKey] = $filterValue;
			}
			$index++;
		}

        $positiveTerms = [];
		if( $positivePlaceholders )
		{
            $positiveTerms[] = $this->schema->getFieldSqlExpression($fieldName)
                . ' IN(' . \implode(', ', $positivePlaceholders) . ')';
		}
        if( $isNullValuePresent )
        {
            $positiveTerms[] = $this->schema->getFieldSqlExpression($fieldName) . ' IS NULL';
        }
        if( $positiveTerms )
        {
            $query->addWhereTerm('(' . join(' OR ', $positiveTerms) . ')');
        }

		if( $negativePlaceholders )
		{
			$query->addWhereTerm(
				$this->schema->getFieldSqlExpression($fieldName)
				. ' NOT IN(' . \implode(', ', $negativePlaceholders) . ')'
			);
		}

		$query->addParams($paramsValues);
	}

	/**
	 * @param string $searchPhraseKey
	 * @param string $searchPhrase
	 * @param array $searchFieldExpressions
	 * @param array $lookupFieldExpressions
	 * @param SqlQuery $query
	 */
	private function applySearchFilter(
		$searchPhraseKey,
		$searchPhrase,
		array $searchFieldExpressions,
		array $lookupFieldExpressions,
		SqlQuery $query
	)
	{
		$subTerms = [];
		$params = [];

		if( $searchFieldExpressions )
		{
			$paramKey = $searchPhraseKey . 'Pattern';
			$params[$paramKey] = $this->buildSearchPatternValue($searchPhrase);
			foreach( $searchFieldExpressions as $sqlExpression )
			{
				$subTerms[] = $this->buildSqlSearchTerm($sqlExpression, $paramKey);
			}
		}

		if( $lookupFieldExpressions )
		{
			$params[$searchPhraseKey] = \trim($searchPhrase);
			foreach( $lookupFieldExpressions as $sqlExpression )
			{
				$subTerms[] = $sqlExpression . ' = :' . $searchPhraseKey;
			}
		}

		if( $subTerms )
		{
			$query->addWhereTerm('(' . \implode(' OR ', $subTerms) . ')', $params);
		}
	}

	/**
	 * @param string $valueExpression
	 * @param string $alias
	 * @return string
	 */
	abstract protected function buildFieldSelectTerm( $valueExpression, $alias );

	/**
	 * @param string $searchPhrase
	 * @return string
	 */
	abstract protected function buildSearchPatternValue( $searchPhrase );

	/**
	 * @param string $valueExpression
	 * @param string $placeholderName
	 * @return string
	 */
	abstract protected function buildSqlSearchTerm( $valueExpression, $placeholderName );

	/**
	 * @param DateTime $dt
	 * @return string
	 */
	abstract protected function dateTimeToDbValue( DateTime $dt );

	/**
	 * @param mixed $dbValue
	 * @return DateTime|null
	 */
	abstract protected function dbToDateTime( $dbValue );
}
