<?php
namespace XAF\test\constraint;

use PHPUnit\Framework\Constraint\Constraint;

class MatchingHashConstraint extends Constraint
{
	/** @var array */
	protected $fieldMatchers;

	/**
     * @param array $fieldMatchers
     */
	public function __construct( array $fieldMatchers )
	{
		$this->fieldMatchers = $fieldMatchers;
	}

	/**
	 * @param mixed $hash Value or object to evaluate.
	 * @return bool
	 */
	protected function matches( $hash ): bool
	{
		foreach( $this->fieldMatchers as $key => $matcher )
		{
			if( !\array_key_exists($key, $hash) || !$this->doesValueMatch($matcher, $hash[$key]) )
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * @param mixed $expectation
	 * @param mixed $actualValue
	 * @return boolean
	 */
	private function doesValueMatch( $expectation, $actualValue )
	{
		if( $expectation instanceof Constraint )
		{
			return $expectation->evaluate($actualValue, '', true);
		}
		// strict comparision only for "FALSE" expectation values ('', 0, [], null, ...),
		// to be able to compare Objects even if they are not the same Instance
		return $expectation ? $expectation == $actualValue : $expectation === $actualValue;
	}

	/**
	 * Returns a string representation of the constraint.
	 *
	 * @return string
	 */
	public function toString(): string
	{
		return 'contains the expected pattern';
	}

	/**
	 * @param array $hash
	 */
	protected function failureDescription( $hash ): string
	{
		$result = 'hash matches expected pattern: ' . "\n";
		foreach( $this->fieldMatchers as $key => $expectation )
		{
			$result .= ' * field "' . $key . '" ';
			if( $expectation instanceof Constraint )
			{
				$result .= $expectation->toString();
			}
			else
			{
				$result .= 'equals ' . \var_export($expectation, true);
			}
			$result .= "\n";
		}

		$result .= "\n";
		$result .= 'Actual values not matching expectations:' . "\n";
		foreach( $this->fieldMatchers as $key => $matcher )
		{
			if( !\array_key_exists($key, $hash) )
			{
				$result .= ' * Field "' . $key . '" is missing' . "\n";
			}
			else if( !$this->doesValueMatch($matcher, $hash[$key]) )
			{
				$result .= ' * Field "' . $key . '" contains ' . \var_export($hash[$key], true) . "\n";
			}
		}
		return $result;
	}
}
