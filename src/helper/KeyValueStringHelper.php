<?php
namespace XAF\helper;

/**
 * Expand/compact hash from/to a human-readable and writable serial string form.
 *
 * Unlike JSON, query string format etc. the format is very liberal and accepts a variety of different syntaxes:
 *
 *     <Key> (:|=) (<Literal>|"<Literal>"|'<Literal>') [,;&<NL>] ...
 *
 * - Keys may only consist of upper and lower letters a to z, numbers, underscore, dot and dash.
 * - All whitespace between key, value and operators is optional.
 * - Literal delimiters inside literals are escaped by duplication.
 */
class KeyValueStringHelper
{
	private function __construct() {}

	/**
	 * @param string $KeyValueString
	 * @return array
	 */
	static public function decode( $KeyValueString )
	{
		//  <Key> (:|=) (<Literal>|"<Literal>"|'<Literal>')
		$pattern = '/([A-Za-z0-9\\._-]+)\\s*[=:]\\s*("(?:[^"]|"")*"|\'(?:[^\']|\'\')*\'|[^,;&\\n]*)/mu';
		if( !\preg_match_all($pattern, $KeyValueString, $matches, \PREG_SET_ORDER) )
		{
			return [];
		}

		$result = [];
		foreach( $matches as $match )
		{
			$key = $match[1];
			$value = \trim($match[2]);
			$result[$key] = self::stripDelimitersAndDecodeEscapes($value);
		}

		return $result;
	}

	static private function stripDelimitersAndDecodeEscapes( $value, array $delimiters = ['"', "'"] )
	{
		$valueLength = \strlen($value);
		if( $valueLength > 1 )
		{
			$firstChar = $value[0];
			$lastChar = $value[$valueLength - 1];
			foreach( $delimiters as $delimiter )
			{
				if( $firstChar == $delimiter && $lastChar == $delimiter )
				{
					$value = \substr($value, 1, $valueLength - 2);
					$value = \str_replace($delimiter . $delimiter, $delimiter, $value);
					break;
				}
			}
		}
		return $value;
	}

	static public function encode( array $hash )
	{
		$resultItems = [];
		foreach( $hash as $key => $value )
		{
			$resultItems[] = $key . ' = ' .
				($value === '' || \ctype_digit($value) ? $value : '"' . \str_replace('"', '""', $value) . '"');
		}
		return \implode(', ', $resultItems);
	}
}
