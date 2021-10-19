<?php
namespace XAF\log\reader;

/**
 * Take a number of stats rows (e.g. a DB query result), each of which consist of a number of key values plus a number
 * and transforms them into a multidimensional hash indexed by the key values.
 *
 * Sums for all possible key combinations are added, using null as key value/hash index for elements providing the sum
 * of all values over that key, much like an SQL GROUP BY WITH ROLLUP or CUBE stement would.
 */
class StatsRollupBuilder
{
	private function __construct() {}

	/**
	 * @param array $rows
	 * @param array $keyFields The field names of the key field found in every row
	 * @param string $valueField The field name of the value (the ones that are summed up) field in every row
	 * @param array $ascendingOrderByFieldKey {<field key>: <bool>, ...} Fields not contained are not ordered
	 * @return array
	 */
	static public function transform( array $rows, array $keyFields, $valueField, array $ascendingOrderByFieldKey = [] )
	{
		$keyValueSets = self::findAndSortAllDistinctKeyValuesInRows($rows, $keyFields, $ascendingOrderByFieldKey);
		$template = self::buildFullKeyCombinationTreeAndSetValuesToZero($keyValueSets);
		return self::addAllRowValuesToResult($rows, $keyFields, $valueField, $template);
	}

	/**
	 * @param array $rows
	 * @param array $sourceFieldKeys
	 * @param array array $hasAscendingOrderByFieldName {<field key>: <bool>, ...} Fields not contained are not ordered
	 * @return array [[<first key value>, <first key value>, ...], [<second key value>, <second key value>, ...], ...]
	 */
	static private function findAndSortAllDistinctKeyValuesInRows( array $rows, array $sourceFieldKeys,
		array $ascendingOrderByFieldKey )
	{
		$result = \array_fill_keys($sourceFieldKeys, []);
		foreach( $rows as $row )
		{
			foreach( $sourceFieldKeys as $sourceFieldKey )
			{
				$keyValue = $row[$sourceFieldKey];
				if( !\in_array($keyValue, $result[$sourceFieldKey]) )
				{
					$result[$sourceFieldKey][] = $keyValue;
				}
			}
		}

		foreach( $result as $sourceFieldKey => &$keyValues )
		{
			if( isset($ascendingOrderByFieldKey[$sourceFieldKey]) )
			{
				if( $ascendingOrderByFieldKey[$sourceFieldKey] )
				{
					\sort($keyValues, \SORT_STRING | \SORT_FLAG_CASE);
				}
				else
				{
					\rsort($keyValues, \SORT_STRING | \SORT_FLAG_CASE);
				}
			}
		}

		return \array_values($result);
	}

	/**
	 * @param array $keyValueSets {<key>: [<value>, ...], ...}
	 * @return array {<key>: {<key>: {...}, ... , null: {...}}, ... , null: {...}}
	 */
	static private function buildFullKeyCombinationTreeAndSetValuesToZero( array $keyValueSets )
	{
		$firstKeyValues = \array_shift($keyValueSets);
		$result = [];
		foreach( $firstKeyValues as $keyValue )
		{
			$result[$keyValue] = $keyValueSets ? self::buildFullKeyCombinationTreeAndSetValuesToZero($keyValueSets) : 0;
		}
		$result[null] = $keyValueSets ? self::buildFullKeyCombinationTreeAndSetValuesToZero($keyValueSets) : 0;
		return $result;
	}

	/**
	 * @param array $rows
	 * @param array $keyFields
	 * @param string $valueField
	 * @param array $template
	 * @return array
	 */
	static private function addAllRowValuesToResult( array $rows, array $keyFields, $valueField, array $template )
	{
		$result = $template;
		foreach( $rows as $row )
		{
			$keyValues = [];
			foreach( $keyFields as $keyField )
			{
				$keyValues[] = $row[$keyField];
			}
			$result = self::addRowValueToResult($keyValues, $row[$valueField], $result);
		}
		return $result;
	}

	/**
	 * @param array $keys
	 * @param numeric $value
	 * @param mixed $result
	 * @return mixed
	 */
	static private function addRowValueToResult( array $keys, $value, $result )
	{
		if( \is_scalar($result) )
		{
			return self::addValues($result, $value);
		}

		$firstKey = \array_shift($keys);
		foreach( [$firstKey, null] as $key )
		{
			$result[$key] = self::addRowValueToResult($keys, $value, $result[$key]);
		}
		return $result;
	}

	/**
	 * Override to implement different sum operation
	 *
	 * @param mixed $value1
	 * @param mixed $value2
	 * @return mixed
	 */
	static protected function addValues( $value1, $value2 )
	{
		return $value1 + $value2;
	}
}
