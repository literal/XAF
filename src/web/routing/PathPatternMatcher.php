<?php
namespace XAF\web\routing;

use XAF\exception\SystemError;

/**
 * Helper for matching path fragments by regex expressions and extracting
 * values from them - not limited to a particular type of path (url, filesystem, control, ...)
 */
class PathPatternMatcher
{
	protected $pathSeparator;
	protected $regexDelimiter = '#';

	protected $regexMatches = [];

	public function __construct( $pathSeparator = '/' )
	{
		$this->pathSeparator = $pathSeparator;

		// make sure path separaor and regex delimiter are not the same character
		if( $this->pathSeparator == '#' )
		{
			$this->regexDelimiter = '/';
		}
	}

	/**
	 * Match a path against a partial preg pattern:
	 * - no delimiters necessary/allowed
	 * - implicit ^ at the beginning
	 * - implicit / or $ at the end
	 *
	 * The pattern 'foo' will match path 'foo', 'foo/' or 'foo/bar' but not '/foo' or 'bar/foo'
	 *
	 * The pattern may contain capturing groups, whose results are used by the
	 * replaceBackrefs() method
	 *
	 * @param string $path
	 * @param string $pattern
	 * @return bool Whether the path matches the pattern
	 */
	public function matchPath( $path, $pattern )
	{
		$this->regexMatches = [];
		$matchResult = @\preg_match($this->buildPregPattern($pattern), $path, $this->regexMatches);
		if( $matchResult === false )
		{
			throw new SystemError('invalid routing pattern', $pattern);
		}

		return $matchResult > 0;
	}

	/**
	 * Build the pattern for matching a path fragment
	 *
	 * A path fragment pattern is always matched against the beginning of a path (fragment) and must
	 * be followed by either a slash or the end of the path.
	 *
	 * Exception: An empty string can only be followed by the end of the path.
	 *
	 * @param string $pregPattern
	 * @return string
	 */
	protected function buildPregPattern( $pregPattern )
	{
		$pregPattern = $pregPattern === '' ? '^$' : '^' . $pregPattern . '(?=/|$)';
		return
			$this->regexDelimiter .
			\str_replace($this->regexDelimiter, '\\' . $this->regexDelimiter, $pregPattern) .
			$this->regexDelimiter;
	}

	/**
	 * Get matching portion of the path from last successful call to matchPath()
	 *
	 * @return string
	 */
	public function getMatchedPathFragment()
	{
		return isset($this->regexMatches[0]) ? $this->regexMatches[0] : null;
	}

	/**
	 * Get replace object for using the groups captured during the last successful call to matchPath()
	 *
	 * @return BackrefReplacer
	 */
	public function getBackrefReplacer()
	{
		return new BackrefReplacer($this->regexMatches);
	}
}
