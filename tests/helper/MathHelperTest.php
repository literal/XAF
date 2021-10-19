<?php
namespace XAF\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\helper\MathHelper
 */
class MathHelperTest extends TestCase
{
	public function testLimitKeepsValueIfNoLimitsSpecified()
	{
		$result = MathHelper::limit(432);

		$this->assertEquals(432, $result);
	}

	public function testLimitKeepsValueIfBetweenMinimumAndMaximum()
	{
		$result = MathHelper::limit(4, 2, 5);

		$this->assertEquals(4, $result);
	}

	public function testLimitFilterLimitsToMinimum()
	{
		$result = MathHelper::limit(1.5, 2, 5);

		$this->assertEquals(2, $result);
	}

	public function testLimitFilterLimitsToMaximum()
	{
		$result = MathHelper::limit(7, 2, 5);

		$this->assertEquals(5, $result);
	}
}
