<?php
namespace XAF\db;

/**
 * Represents the parts of an SQL select statement and the prepared statement parameters
 * Used for constructing (complex) SQL queries in multiple steps
 */
class SqlQuery
{
	/** @var array */
	private $selectFields = [];

	private $tableName;

	/** @var array */
	private $joins = [];

	/** @var array */
	private $whereConditions = [];

	/** @var array */
	private $orderByExpressions = [];

	/** @var array */
	private $groupByExpressions = [];

	/** @var int|null */
	private $limitCount;

	/** @var int|null */
	private $limitOffset;

	/** @var array */
	private $params = [];

	/**
	 * @param string|null $tableName
	 */
	public function __construct( $tableName = null )
	{
		if( $tableName !== null )
		{
			$this->setTableName($tableName);
		}
	}

	public function clear()
	{
		$this->selectFields = [];
		$this->whereConditions = [];
		$this->joins = [];
		$this->orderByExpressions = [];
		$this->groupByExpressions = [];
		$this->limitCount = null;
		$this->limitOffset = null;
		$this->params = [];
	}

	/**
	 * @param string $term
	 */
	public function addFieldTerm( $term )
	{
		$this->selectFields[] = $term;
	}

	public function clearFields()
	{
		$this->selectFields = [];
	}

	/**
	 * @param string $tableName
	 */
	public function setTableName( $tableName )
	{
		$this->tableName = $tableName;
	}

	/**
	 * @param string $term
	 * @param string $type
	 */
	public function addJoinTerm( $term, $type = 'INNER' )
	{
		$this->joins[] = $type . ' JOIN ' . $term;
	}

	/**
	 * @param string $term
	 * @param array $params
	 */
	public function addWhereTerm( $term, $params = [] )
	{
		$this->whereConditions[] = $term;
		$this->addParams($params);
	}

	/**
	 * @param string $term
	 */
	public function addOrderByTerm( $term )
	{
		$this->orderByExpressions[] = $term;
	}

	public function clearOrderBy()
	{
		$this->orderByExpressions = [];
	}

	/**
	 * @param string $term
	 */
	public function addGroupByTerm( $term )
	{
		$this->groupByExpressions[] = $term;
	}

	/**
	 * @param int|null $maxCount
	 * @param int|null $offset
	 */
	public function setLimit( $maxCount, $offset = null )
	{
		$this->limitCount = $maxCount;
		$this->limitOffset = $offset;
	}

	/**
	 * @param array $params
	 */
	public function addParams( array $params )
	{
		if( $params )
		{
			$this->params = \array_replace($this->params, $params);
		}
	}

	/**
	 * @return string
	 */
	public function getSqlStatement()
	{
		return
			'SELECT ' . $this->getFieldsExpression() .
			' FROM ' . $this->tableName . $this->getJoinExpression() .
			$this->getWhereExpression() .
			$this->getGroupByExpression() .
			$this->getOrderByExpression() .
			$this->getLimitExpression();
	}

	/**
	 * @return string
	 */
	public function getFieldsExpression()
	{
		return $this->selectFields
			? \implode(', ', $this->selectFields)
			: '*';
	}

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return $this->tableName;
	}

	/**
	 * @return string
	 */
	public function getJoinExpression()
	{
		return $this->joins
			? ' ' . \implode(' ', $this->joins)
			: '';
	}

	/**
	 * @return string
	 */
	public function getWhereExpression()
	{
		return $this->whereConditions
			? ' WHERE ' . \implode(' AND ', $this->whereConditions)
			: '';
	}

	/**
	 * @return string
	 */
	public function getOrderByExpression()
	{
		return $this->orderByExpressions
			? ' ORDER BY ' . \implode(', ', $this->orderByExpressions)
			: '';
	}

	/**
	 * @return string
	 */
	public function getGroupByExpression()
	{
		return $this->groupByExpressions
			? ' GROUP BY ' . \implode(', ', $this->groupByExpressions)
			: '';
	}

	/**
	 * @return string
	 */
	public function getLimitExpression()
	{
		return isset($this->limitCount)
			? ' LIMIT ' . $this->limitCount . (isset($this->limitOffset) ? ' OFFSET ' . $this->limitOffset : '')
			: '';
	}

	/**
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}
}
