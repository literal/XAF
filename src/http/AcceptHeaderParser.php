<?php
namespace XAF\http;

class AcceptHeaderParser
{
	/**
	 * Parse any HTTP accept header (e.g. "Accept-Language: ...") and return an array of the contained items
	 * ordered by preference ("q=" params)
	 *
	 * @param string $header
	 * @return array
	 */
	static public function parse( $header )
	{
		$results = [];
		$weights = [];

		foreach( \explode(',', $header) as $item )
		{
			$parts = \explode(';', $item, 2);
			$subject = \trim($parts[0]);
			if( isset($parts[1]) && \preg_match('/q\\s*=\\s*([0-1]\\.[0-9]+|\\.[0-9]+|[0-1])/', $parts[1], $matches) )
			{
				$weight = \floatval($matches[1]);
			}
			else
			{
				$weight = 1;
			}
			$results[] = $subject;
			$weights[] = $weight;
		}
		\array_multisort($weights, \SORT_DESC, \array_keys($results), \SORT_ASC, $results);
		return $results;
	}
}
