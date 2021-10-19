<?php
namespace XAF\test;

use PHPUnit\Framework\Constraint\Constraint as PhpUnitConstraint;

/**
 * Helper Trait for using in PHPUnit TestCase classes.
 * Adds assert methods to check contents of hashes and arrays of hashes.
 */
trait HashAssertMethods
{
	/**
	 * @param array $fieldMatchers {<string key>: <mixed value>, ...}
	 *    key: field name
	 *    value: a PHPUnit matcher (Constraint) or a value for an equality check
	 * @param array $hash
	 */
	protected function assertHashMatches( array $fieldMatchers, array $hash )
	{
		HashAssert::assertHashMatches($fieldMatchers, $hash);
	}

	/**
	 * @param array $fieldMatchers {<string key>: <mixed value>, ...}
	 *    key: field name
	 *    value: a PHPUnit matcher (Constraint) or a value for an equality check
	 * @param array $hashes [<hash>, ...]
	 */
	protected function assertListContainsOneMatchingHash( array $fieldMatchers, array $hashes )
	{
		HashAssert::assertListContainsOneMatchingHash($fieldMatchers, $hashes);
	}

	/**
	 * @param array $fieldMatchers {<string key>: <mixed value>, ...}
	 *    key: field name
	 *    value: a PHPUnit matcher (Constraint) or a value for an equality check
	 * @param array $hashes [<hash>, ...]
	 */
	protected function assertListContainsNoMatchingHash( array $fieldMatchers, array $hashes )
	{
		HashAssert::assertListContainsNoMatchingHash($fieldMatchers, $hashes);
	}

	/**
	 * @param int $expectedCount
	 * @param array $fieldMatchers {<string key>: <mixed value>, ...}
	 *    key: field name
	 *    value: a PHPUnit matcher (Constraint) or a value for an equality check
	 * @param array $hashes [<hash>, ...]
	 */
	protected function assertListContainsNumberOfMatchingHashes( $expectedCount, array $fieldMatchers, array $hashes )
	{
		HashAssert::assertListContainsNumberOfMatchingHashes($expectedCount, $fieldMatchers, $hashes);
	}

	/**
	 * @param array $fieldMatchers {<string key>: <mixed value>, ...}
	 *    key: field name
	 *    value: a PHPUnit matcher (Constraint) or a value for an equality check
	 * @return PhpUnitConstraint
	 */
	protected function doesHashMatch( array $fieldMatchers )
	{
		return HashAssert::doesHashMatch($fieldMatchers);
	}

	/**
	 * @param array $fieldMatchers {<string key>: <mixed value>, ...}
	 *    key: field name
	 *    value: a PHPUnit matcher (Constraint) or a value for an equality check
	 * @return PhpUnitConstraint
	 */
	protected function doesHashListContainOneMatchingHash( array $fieldMatchers )
	{
		return HashAssert::doesHashListContainOneMatchingHash($fieldMatchers);
	}

	/**
	 * @param array $fieldMatchers {<string key>: <mixed value>, ...}
	 *    key: field name
	 *    value: a PHPUnit matcher (Constraint) or a value for an equality check
	 * @return PhpUnitConstraint
	 */
	protected function doesHashListNotContainMatchingHash( array $fieldMatchers )
	{
		return HashAssert::doesHashListNotContainMatchingHash($fieldMatchers);
	}

	/**
	 * @param int $expectedCount
	 * @param array $fieldMatchers {<string key>: <mixed value>, ...}
	 *    key: field name
	 *    value: a PHPUnit matcher (Constraint) or a value for an equality check
	 * @return PhpUnitConstraint
	 */
	protected function doesHashListContainNumberOfMatchingHashes( $expectedCount, array $fieldMatchers )
	{
		return HashAssert::doesHashListContainNumberOfMatchingHashes($expectedCount, $fieldMatchers);
	}
}
