<?php
namespace XAF\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\helper\HashHelper
 */
class HashHelperTest extends TestCase
{
	public function testHashesWithoutCommonFieldsAreReportedCommonEqual()
	{
		$result = HashHelper::areAllCommonFieldsEqual(
			['foo' => 'foo'],
			['bar' => 'bar']
		);

		$this->assertTrue($result);
	}

	public function testHashesWithEqualCommonFieldsAreReportedCommonEqual()
	{
		$result = HashHelper::areAllCommonFieldsEqual(
			['foo' => 'foo', 'bar' => 'bar'],
			['foo' => 'foo', 'boom' => 'boom']
		);

		$this->assertTrue($result);
	}

	public function testHashesWithDifferentCommonFieldsAreReportedNonCommonEqual()
	{
		$result = HashHelper::areAllCommonFieldsEqual(
			['foo' => 'foo'],
			['foo' => 'bar']
		);

		$this->assertFalse($result);
	}

	/**
	 * Make sure 'null' is not treated as "field not present".
	 */
	public function testNullIsComparedInHashCommonFields()
	{
		$result = HashHelper::areAllCommonFieldsEqual(
			['foo' => null],
			['foo' => 'foo']
		);

		$this->assertFalse($result);
	}

	public function testRemoveNullFieldsRemovesNullFields()
	{
		$result = HashHelper::removeNullFields(['foo' => null, 'bar' => 'boom']);

		$this->assertEquals(['bar' => 'boom'], $result);
	}

	public function testRemoveNullFieldsKeepsEmptyFields()
	{
		$result = HashHelper::removeNullFields(['foo' => '', 'bar' => 0, 'boom' => false]);

		$this->assertEquals(['foo' => '', 'bar' => 0, 'boom' => false], $result);
	}

	public function testTransformByMapRenamesFieldsToTargetKey()
	{
		$result = HashHelper::transformByMap(['foo' => 1], ['foo' => 'targetFoo']);

		$this->assertEquals(['targetFoo' => 1], $result);
	}

	public function testTransformByMapIgnoresUmappedFields()
	{
		$result = HashHelper::transformByMap(['foo' => 1, 'bar' => 2], ['foo' => 'foo']);

		$this->assertEquals(['foo' => 1], $result);
	}

	public function testTransformByMapIgnoresNonPresentFields()
	{
		$result = HashHelper::transformByMap(['bar' => 1], ['foo' => 'foo', 'bar' => 'bar']);

		$this->assertEquals(['bar' => 1], $result);
	}

	public function testTransformByMapCopiesNullFields()
	{
		$result = HashHelper::transformByMap(['foo' => null], ['foo' => 'foo']);

		$this->assertEquals(['foo' => null], $result);
	}

	public function testTransformByMapReturnsEmptyHashWhenNoFieldsAreToBeCopied()
	{
		$result = HashHelper::transformByMap(['foo' => null], ['bar' => 'bar']);

		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}
}
