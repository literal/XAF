<?php
namespace XAF\test;

use PHPUnit\Framework\Constraint\Constraint as PhpUnitConstraint;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\InvalidArgumentException;

/**
 * Extension of the PHPUnit Assert Class to deal with Hashes and Lists of Hashes
 */
class HashAssert
{
	/**
	 * @param array $fieldMatchers {<string key>: <mixed value>, ...}
	 *    key: field name
	 *    value: a PHPUnit matcher (Constraint) or a value for an equality check
	 * @param array $hash
	 */
	static public function assertHashMatches( array $fieldMatchers, array $hash )
	{
		Assert::assertThat($hash, new constraint\MatchingHashConstraint($fieldMatchers));
	}

	/**
	 * @param array $fieldMatchers {<string key>: <mixed value>, ...}
	 *    key: field name
	 *    value: a PHPUnit matcher (Constraint) or a value for an equality check
	 * @param array $hashes [<hash>, ...]
	 */
	static public function assertListContainsOneMatchingHash( array $fieldMatchers, array $hashes )
	{
		self::assertListContainsNumberOfMatchingHashes(1, $fieldMatchers, $hashes);
	}

	/**
	 * @param array $fieldMatchers {<string key>: <mixed value>, ...}
	 *    key: field name
	 *    value: a PHPUnit matcher (Constraint) or a value for an equality check
	 * @param array $hashes [<hash>, ...]
	 */
	static public function assertListContainsNoMatchingHash( array $fieldMatchers, array $hashes )
	{
		self::assertListContainsNumberOfMatchingHashes(0, $fieldMatchers, $hashes);
	}

	/**
	 * @param int $expectedCount
	 * @param array $fieldMatchers {<string key>: <mixed value>, ...}
	 *    key: field name
	 *    value: a PHPUnit matcher (Constraint) or a value for an equality check
	 * @param array $hashes [<hash>, ...]
	 */
	static public function assertListContainsNumberOfMatchingHashes( $expectedCount, array $fieldMatchers,
		array $hashes )
	{
		if( !\is_int($expectedCount) )
		{
			throw InvalidArgumentException::create(1, 'int');
		}

		Assert::assertThat($hashes, new constraint\ContainsNumberOfMatchingHashesConstraint($expectedCount, $fieldMatchers));
	}

	/**
	 * @param array $fieldMatchers {<string key>: <mixed value>, ...}
	 *    key: field name
	 *    value: a PHPUnit matcher (Constraint) or a value for an equality check
	 * @return PhpUnitConstraint
	 */
	static public function doesHashMatch( array $fieldMatchers )
	{
		if( !\is_array($fieldMatchers) )
		{
			throw InvalidArgumentException::create(1, 'array');
		}
		return new constraint\MatchingHashConstraint($fieldMatchers);
	}

	/**
	 * @param array $fieldMatchers {<string key>: <mixed value>, ...}
	 *    key: field name
	 *    value: a PHPUnit matcher (Constraint) or a value for an equality check
	 * @return PhpUnitConstraint
	 */
	static public function doesHashListContainOneMatchingHash( array $fieldMatchers )
	{
		if( !\is_array($fieldMatchers) )
		{
			throw InvalidArgumentException::create(1, 'array');
		}
		return new constraint\ContainsNumberOfMatchingHashesConstraint(1, $fieldMatchers);
	}

	/**
	 * @param array $fieldMatchers {<string key>: <mixed value>, ...}
	 *    key: field name
	 *    value: a PHPUnit matcher (Constraint) or a value for an equality check
	 * @return PhpUnitConstraint
	 */
	static public function doesHashListNotContainMatchingHash( array $fieldMatchers )
	{
		if( !\is_array($fieldMatchers) )
		{
			throw InvalidArgumentException::create(1, 'array');
		}
		return new constraint\ContainsNumberOfMatchingHashesConstraint(0, $fieldMatchers);
	}

	/**
	 * @param int $expectedCount
	 * @param array $fieldMatchers {<string key>: <mixed value>, ...}
	 *    key: field name
	 *    value: a PHPUnit matcher (Constraint) or a value for an equality check
	 * @return PhpUnitConstraint
	 */
	static public function doesHashListContainNumberOfMatchingHashes( $expectedCount, array $fieldMatchers )
	{
		if( !\is_int($expectedCount) )
		{
			throw InvalidArgumentException::create(1, 'int');
		}
		if( !\is_array($fieldMatchers) )
		{
			throw InvalidArgumentException::create(2, 'array');
		}
		return new constraint\ContainsNumberOfMatchingHashesConstraint($expectedCount, $fieldMatchers);
	}
}
