<?php
namespace XAF\view\helper;

use PHPUnit\Framework\TestCase;

/**
 * Only the differences to NumberHelper are tested
 *
 * @covers \XAF\view\helper\NumberHelperDe
 */
class NumberHelperDeTest extends TestCase
{
	/** @var NumberHelperDe */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new NumberHelperDe();
	}

	public function testNumber()
	{
		$result = $this->object->formatNumber(123456789.12, 2);
		$this->assertEquals('123.456.789,12', $result);
	}
}
