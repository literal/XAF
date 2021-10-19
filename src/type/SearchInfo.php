<?php
namespace XAF\type;

/**
 * Public data object for details of a search operation
 */
class SearchInfo
{
	/**
	 * @var string The complete search phrase as entered by the user
	 */
	public $phrase = '';

	/**
	 * @var array split version of the search phrase
	 */
	public $terms = [];

	/**
	 * @var string preg pattern for e.g. highlighting of matches
	 */
	public $pregPattern;
}
