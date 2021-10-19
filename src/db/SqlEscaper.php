<?php
namespace XAF\db;

class SqlEscaper
{
	/**
	 * @param string $string
	 * @return array 
	 */
	static public function likeEscape( $string )
	{
		return \strtr(
			$string,
			[
				'\\' => '\\\\',
				'%' => '\\%',
				'_' => '\\_'
			]
		);
	}

	/**
	 * @param string $string
	 * @return array 
	 */
	static public function regexEscape( $string )
	{
		return \strtr(
			$string,
			[
				'^' => '\\^',
				'$' => '\\$',
				'.' => '\\.',
				'(' => '\\(',
				')' => '\\)',
				'{' => '\\{',
				'}' => '\\}',
				'[' => '\\[',
				']' => '\\]',
				'|' => '\\|',
				'?' => '\\?',
				'+' => '\\+',
				'*' => '\\*',
				'\\' => '\\\\'
			]
		);
	}
}
