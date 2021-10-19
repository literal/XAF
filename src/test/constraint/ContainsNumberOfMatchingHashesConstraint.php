<?php
namespace XAF\test\constraint;

use PHPUnit\Framework\Constraint\Constraint;

class ContainsNumberOfMatchingHashesConstraint extends Constraint
{
	/** @var int */
	protected $expectedCount;

	/** @var array */
	protected $fieldMatchers;

	/**
	 * @param int $expectedCount
	 * @param array $fieldMatchers {<string key>: <mixed value>, ...}
	 *    key: field name
	 *    value: a PHPUnit matcher (Constraint) or a value for an equality check
	 */
	public function __construct( $expectedCount, array $fieldMatchers )
	{
		$this->expectedCount = $expectedCount;
		$this->fieldMatchers = $fieldMatchers;
	}

	/**
	 * @param mixed $hashList Value or object to evaluate.
	 * @return bool
	 */
	protected function matches( $hashList ): bool
	{
		$numberOfMatches = 0;
		foreach( $hashList as $hash )
		{
			$constraint = new MatchingHashConstraint($this->fieldMatchers);
			if( $constraint->evaluate($hash, '', true) )
			{
				$numberOfMatches++;
			}
		}
		return $numberOfMatches === $this->expectedCount;
	}

	/**
	 * @return string
	 */
	public function toString(): string
	{
		return 'contains the expected pattern ' . $this->expectedCount . ' times';
	}
}
