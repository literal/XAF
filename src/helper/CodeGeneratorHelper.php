<?php
namespace XAF\helper;

class CodeGeneratorHelper
{
	/**
	 * @param string $string
	 * @return string
	 */
	static public function toUnderscoreIdentifier( $string )
	{
		return \trim(\preg_replace('/[^a-z0-9]+/', '_', \strtolower($string)), '_');
	}

	/**
	 * @param string $string
	 * @return string
	 */
	static public function toTitleCaseIdentifier( $string )
	{
		\preg_match_all('/[a-z0-9]+/i', \strtolower($string), $matches);
		return \implode('', \array_map('ucfirst', $matches[0]));
	}

	/**
	 * @param string $string
	 * @return string
	 */
	static public function toCamelCaseIdentifier( $string )
	{
		return \lcfirst(self::toTitleCaseIdentifier($string));
	}

	/**
	 * @param string $string
	 * @return string
	 */
	static public function camelCaseToWords( $string )
	{
		$result = '';
		for( $i = 0, $l = \mb_strlen($string); $i < $l; $i++ )
		{
			$c = \mb_substr($string, $i, 1);
			$result .= self::isUpperCaseChar($c) ? ' ' . \mb_strtolower($c) : $c;
		}
		return \trim($result);
	}

	/**
	 * @param string $char
	 * @return boolean
	 */
	static private function isUpperCaseChar( $char )
	{
		return \mb_strtolower($char) !== $char;
	}

	/**
	 * @param string $string
	 * @return string
	 */
	static public function regexEscape( $string )
	{
		return \preg_quote($string);
	}

	/**
	 * @param string $string
	 * @return string
	 */
	static public function toPhpStringLiteral( $string )
	{
		return "'" . \addcslashes($string, '\\\'') . "'";
	}
}
