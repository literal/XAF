<?php
namespace XAF\type;

/**
 * Split search phrases and buid preg search patterns
 */
class SearchRequest
{
	/** @var the original serach phrase */
	private $phrase;

	/** @var array groups/words the search phase was split into */
	private $terms;

	/**
	 * @param string $phrase
	 */
	public function __construct( $phrase )
	{
		$this->phrase = $phrase;
		$this->terms = $this->splitIntoTerms($this->phrase);
	}

	/**
	 * - Word groups in double quotes are kept
	 * - Multiple whitespace chars are reduced to single space
	 * - All non alpha chars are removed outside double quote
	 *
	 * @param string $searchPhrase
	 * @return array
	 */
	private function splitIntoTerms( $searchPhrase )
	{
		$result = [];

		if( \trim($searchPhrase) === '' )
		{
			return [];
		}

		// Extract all quotes phrases and words
		if( \preg_match_all('#"[^"]*"|[^\\s]+#u', $searchPhrase, $matches) )
		{
			foreach( $matches[0] as $match )
			{
				$term = \preg_replace('/\\s+/', ' ', $match);
				$term = \trim($term, ' "');
				if( $term !== '' )
				{
					$result[] = $term;
				}
			}
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function isEmpty()
	{
		return \sizeof($this->terms) < 1;
	}

	/**
	 * @return string
	 */
	public function getPhrase()
	{
		return $this->phrase;
	}

	/**
	 * @return array
	 */
	public function getTerms()
	{
		return $this->terms;
	}

	/**
	 * Return Perl regex pattern for *any* of the search terms, i. e. the pattern will match all occurrences of
	 * any of the search words in a subject. Intended for finding search words in retrieved search results
	 * (e. g. for highlighting).
	 *
	 * @return string|null
	 */
	public function getPregSearchPattern()
	{
		if( \sizeof($this->terms) < 1 )
		{
			return null;
		}

		$patterns = [];
		foreach( $this->terms as $phrase )
		{
			$patterns[] = \str_replace(' ', '\\s+', \preg_quote($phrase, '/'));
		}
		return '/(' . \implode('|', $patterns) . ')/iu'; // Modifier u: UTF-8
	}
}
