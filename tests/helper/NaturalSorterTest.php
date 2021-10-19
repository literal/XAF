<?php
namespace XAF\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\helper\NaturalSorter
 */
class NaturalSorterTest extends TestCase
{
	public function testNumbersAreComparedByValue()
	{
		$result = NaturalSorter::sort(['a/b/101_123', 'a/b/12_123', 'a/b/000001_123']);

		$this->assertEquals(['a/b/000001_123', 'a/b/12_123', 'a/b/101_123'], $result);
	}

	public function testSecondaryNumbersAreComparedByValue()
	{
		$result = NaturalSorter::sort(['a/b01-0010x', 'a/b01-2y']);

		$this->assertEquals(['a/b01-2y', 'a/b01-0010x'], $result);
	}

	public function testComparisonIsCaseInsensitive()
	{
		$result = NaturalSorter::sort(['ITEM2', 'item1']);

		$this->assertEquals(['item1', 'ITEM2'], $result);
	}

	public function testAnyGroupsOfNonWordCharactersAreTreatedAsIdentical()
	{
		$this->assertEquals(['foo<<.1', 'foo.2'], NaturalSorter::sort(['foo.2', 'foo<<.1']));
		$this->assertEquals(['foo.1', 'foo<<.2'], NaturalSorter::sort(['foo<<.2', 'foo.1']));
	}
}
