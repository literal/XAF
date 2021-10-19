<?php
namespace XAF\view\helper;

class StringHelper
{
	/**
	 * @param string $text
	 * @param int $length
	 * @param string $ellipsisPostfix
	 * @return string
	 */
	public function truncate( $text, $length = 10, $ellipsisPostfix = '…' )
	{
		return \mb_strlen($text) > $length
			? \mb_substr($text, 0, $length) . $ellipsisPostfix
			: $text;
	}

	/**
	 * @param string $text
	 * @param int $maxParts
	 * @param string $partSeparator
	 * @param string $omittedPartPostfix
	 * @return string
	 */
	public function limitParts( $text, $maxParts = 2, $partSeparator = ',', $omittedPartPostfix = ' …' )
	{
		if( \mb_substr_count($text, $partSeparator) < $maxParts )
		{
			return $text;
		}

		$parts = \explode($partSeparator, $text);
		$partsToKeep = \array_slice($parts, 0, $maxParts);
		return \implode($partSeparator, $partsToKeep) . $omittedPartPostfix;
	}

	/**
	 * Cut off the remaining part of a string from the first occurrence of an invalid/unwanted/unexpected character
	 *
	 * @param string $text
	 * @param string $validCharPregClass A Perl-Regex character class expression (without the surrounding '[' and ']')
	 * @return string
	 */
	public function cutInvalidRemainder( $text, $validCharPregClass = 'A-Za-z0-9_-')
	{
		\preg_match('/^([' . $validCharPregClass . ']*)/u', $text, $matches);
		// No match if the input is invalid UTF-8 - just discard whole string then
		return $matches[1] ?? '';
	}
}
