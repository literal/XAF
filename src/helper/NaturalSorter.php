<?php
namespace XAF\helper;

/**
 * Sort a set of string values in a semantic way. Similar to PHP's natcasesort() but smarter.
 *
 * - All numbers occurring in the compared values are compared by value instead of alphanumerically
 * - All groups of non-alphanumeric chars are treated as equivalent
 * - Values are compared case-insensitively
 */
class NaturalSorter
{
	private function __construct() {}

	/**
	 * @param array $values
	 * @return array
	 */
	static public function sort( array $values )
	{
		$sortTokens = [];
		foreach( \array_values($values) as $file )
		{
			// Remove leading zeroes of all numbers in file (as PHP natural sorting only ignores them at the very beginning)
			// Replace all groups of non-alpha chars with underscores to treat them as identical when sorting
			$sortTokens[] = \preg_replace(['/(?<=\\D|^)0+(\\d+)/', '/\\W+/'], ['$1', '_'], $file);
		}
		\array_multisort($sortTokens, \SORT_ASC, \SORT_NATURAL | \SORT_FLAG_CASE, $values);
		return \array_values($values);
	}
}
