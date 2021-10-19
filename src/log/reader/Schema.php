<?php
namespace XAF\log\reader;

use XAF\exception\SystemError;

/**
 * Represents the log data's database schema and how it is
 */
class Schema
{
	/** @var array */
	private $fields;

	/** @var string */
	private $orderBySqlExpression;

	/**
	 * @param array $fields {<field name> => <field definition>, ...}
	 *     All fields in <field definition> are optional. Supported fields:
	 *     {
	 *         sql: <string>,       // SQL expression for getting the field value - if omitted, the field name is used
	 *         convert: <string|callable>, // Conversion rule for the field. Either a token for the conversion
	 *                                     // operation understood by the user of the schema or a callable of type:
	 *                                     //    function( mixed $value, array $row ) : mixed
	 *                                     //    ... where the converted value is the return value and $row contains
	 *                                     //    the whole row the value was part of and is only used by conversions
	 *                                     //    that depend on other field's values.
	 *         eventTime: <bool>,   // Marks the column as providing the primary timestamp of the logged event.
	 *                              // Only the first such column is used.
	 *         lookupBy: <string>,  // Include row into the result the search phrase of the given type
	 *                              // matches the field value exactly
	 *         searchBy: <string>,  // Include row into the result if the search phrase of the given type
	 *                              // is contained in the field value
	 *         orderDesc: <bool>    // Order descending if result is ordered by this field
	 *     }
	 * @param string $orderBySqlExpression
	 */
	public function __construct( array $fields, $orderBySqlExpression )
	{
		$this->fields = $fields;
		$this->orderBySqlExpression = $orderBySqlExpression;
	}

	/**
	 * @param string $fieldName
	 * @return string
	 */
	public function getFieldSqlExpression( $fieldName )
	{
		$this->assertFieldExists($fieldName);
		return $this->buildSqlExpression($fieldName);
	}

	/**
	 * @return string
	 */
	public function getEventTimeSqlExpression()
	{
		foreach( $this->fields as $fieldName => $fieldDef )
		{
			if( $this->getFieldFlagValue($fieldName, 'eventTime') )
			{
				return $this->buildSqlExpression($fieldName);
			}
		}

		throw new SystemError('no event time field defined');
	}

	/**
	 * @return array {<field name>: <SQL expression>}
	 */
	public function getAllFieldsSqlExpressions()
	{
		$result = [];
		foreach( $this->fields as $fieldName => $fieldDef )
		{
			$result[$fieldName] = $this->buildSqlExpression($fieldName);
		}
		return $result;
	}

	/**
	 * @return array {
	 *     <search phrase key>: {
	 *         lookup: [<SQL expression>, ...],
	 *         search: [<SQL expression>, ...]
	 *     },
	 *     ...
	 * }
	 */
	public function getSearchAndLookupFieldSqlExpressionsBySearchPhraseKey()
	{
		$result = [];
		foreach( $this->fields as $fieldName => $fieldDef )
		{
			if( isset($fieldDef['lookupBy']) )
			{
				$searchPhraseKey = $fieldDef['lookupBy'];
				$result[$searchPhraseKey]['lookup'][] = $this->buildSqlExpression($fieldName);
			}
			if( isset($fieldDef['searchBy']) )
			{
				$searchPhraseKey = $fieldDef['searchBy'];
				$result[$searchPhraseKey]['search'][] = $this->buildSqlExpression($fieldName);
			}
		}
		return $result;
	}

	/**
	 * @param string $fieldName
	 * @return string
	 */
	public function getFieldOrderByExpression( $fieldName )
	{
		return $this->buildSqlExpression($fieldName) . ' '
			. ($this->isAscendingOrderField($fieldName) ? 'ASC' : 'DESC');
	}

	public function isAscendingOrderField( $fieldName )
	{
		$this->assertFieldExists($fieldName);
		return !$this->getFieldFlagValue($fieldName, 'orderDesc');
	}

	public function doesFieldExist( $fieldName )
	{
		return isset($this->fields[$fieldName]);
	}

	/**
	 * @param string $fieldName
	 */
	public function assertFieldExists( $fieldName )
	{
		if( !isset($this->fields[$fieldName]) )
		{
			throw new SystemError('Unknown field', $fieldName);
		}
	}

	/**
	 * @return string
	 */
	public function getGlobalOrderBySqlExpression()
	{
		return $this->orderBySqlExpression;
	}

	/**
	 * @return array {<field name>: <common conversion key or callable>, ...}
	 */
	public function getFieldValueConversionKeys()
	{
		return $this->getFieldProperties('convert');
	}

	/**
	 * @param string $fieldName
	 * @param string $flagKey
	 * @return bool
	 */
	private function getFieldFlagValue( $fieldName, $flagKey )
	{
		return isset($this->fields[$fieldName])
			? isset($this->fields[$fieldName][$flagKey]) && $this->fields[$fieldName][$flagKey]
			: false;
	}

	/**
	 * Return propert< values for all fields having the property defined
	 *
	 * @param string $propertyKey
	 * @return array {<field name>: <property value>, ...}
	 */
	private function getFieldProperties( $propertyKey )
	{
		$result = [];
		foreach( $this->fields as $fieldName => $fieldDef )
		{
			if( isset($fieldDef[$propertyKey]) )
			{
				$result[$fieldName] = $fieldDef[$propertyKey];
			}
		}
		return $result;
	}

	/**
	 * @param string $fieldName
	 * @return string
	 */
	private function buildSqlExpression( $fieldName )
	{
		return $this->fields[$fieldName]['sql'] ?? $fieldName;
	}
}
