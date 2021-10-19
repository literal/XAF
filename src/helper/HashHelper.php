<?php
namespace XAF\helper;

/**
 * Stateless collection of general helpers for hashes (associative arrays)
 */
class HashHelper
{
	/**
	 * Check if all elements that exist in *both* hashes have the same values.
	 *
	 * @param array $hash1
	 * @param array $hash2
	 * @return bool
	 */
	static public function areAllCommonFieldsEqual( array $hash1, array $hash2 )
	{
		foreach( $hash1 as $key => $hash1value )
		{
			if( \array_key_exists($key, $hash2) && $hash2[$key] != $hash1value )
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Remove all elements containing null values.
	 *
	 * @param array $hash
	 * @return array
	 */
	static public function removeNullFields( array $hash )
	{
		$result = [];
		foreach( $hash as $key => $value )
		{
			if( $value !== null )
			{
				$result[$key] = $value;
			}
		}
		return $result;
	}

	/**
	 * Translate a hash into another using only field which both exist in the source and are mentioned in the map
	 *
	 * @param array $source
	 * @param array $fieldMap {<sourceFieldKey> => <targetFieldKey>, ...}
	 * @return array
	 */
	static public function transformByMap( array $source, array $fieldMap )
	{
		$result = [];
		foreach( $fieldMap as $sourceKey => $targetKey )
		{
			if( \array_key_exists($sourceKey, $source) )
			{
				$result[$targetKey] = $source[$sourceKey];
			}
		}
		return $result;
	}
}
