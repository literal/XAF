<?php
use XAF\test\HashAssertMethods;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\test\HashAssertMethods
 * @covers \XAF\test\HashAssert
 * @covers \XAF\test\constraint\MatchingHashConstraint
 * @covers \XAF\test\constraint\ContainsNumberOfMatchingHashesConstraint
 *
 * THis is a special test. It tests the test helpers, i.e. the asserts.
 */
class HashAssertMethodsTest extends TestCase
{
	use HashAssertMethods;

	public function testAssertHashMatchesEqualHash()
	{
		$this->assertHashMatches(['foo' => 123], ['foo' => 123]);
	}

	public function testAssertHashMatchesWithMatcherObjectforValue()
	{
		$this->assertHashMatches(['bar' => $this->stringContains('xy')], ['bar' => 'abcxy.']);
	}

	public function testAssertHashMatchesPartialHash()
	{
		$this->assertHashMatches(['bar' => 'x'], ['foo' => 123, 'bar' => 'x']);
	}
}
