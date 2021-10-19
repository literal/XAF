<?php
namespace XAF\web\routing;

/**
 * Replaces backrefs from a regex match
 *
 * Usually returned as a result from PathPatternMatcher
 */
class BackrefReplacer
{
	/**
	 * @var array
	 */
	protected $replacePairs = ['$$' => '$'];

	/**
	 * @param array $regexMatches scalar array of captured (sub-)pattern matches as returned from \preg_match
	 */
	public function __construct( array $regexMatches )
	{
		$this->buildReplacePairs($regexMatches);
	}

	/**
	 * Build a hash of string replace pairs for application of captured regex groups
	 *
	 * @param array $regexMatches
	 */
	protected function buildReplacePairs( array $regexMatches )
	{
		$this->replacePairs = ['$$' => '$'];
		for( $i = 0; $i < 10; $i++ )
		{
			$value = isset($regexMatches[$i]) ? $regexMatches[$i] : '';
			$this->replacePairs['$' . $i] = $value;
			$this->replacePairs['$u' . $i] = \ucfirst($value);
		}
	}

	/**
	 * Replaces occurrences of $0, $1, $2 etc. and $u0, $u1, $u2 in the subject,
	 * where $u yields the value with the first letter upper-cased (for use in
	 * object aliasses or method names, e.g. 'get$u1')
	 *
	 * The escapre sequence for a literal dollar sign in the subject is '$$'
	 *
	 * Uses the values matches from the last successful call to matchPath()
	 *
	 * @param string $subject
	 * @return string
	 */
	public function process( $subject )
	{
		return \strtr($subject, $this->replacePairs);
	}
}
