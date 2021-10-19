<?php
namespace XAF\helper;

class JavascriptHelper
{
	/**
	 * Convert value into a Javascript literal expression
	 *
	 * @param mixed $value
	 * @return string
	 */
	static public function buildLiteral( $value )
	{
		return \json_encode($value, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
	}
}
