<?php
namespace XAF\log\reader;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\log\reader\StatsRollupBuilder
 */
class StatsRollupBuilderTest extends TestCase
{
	public function test()
	{
		$result = StatsRollupBuilder::transform(
			[
				['key1' => 'foo', 'key2' => 'bar', 'value' => 8],
				['key1' => 'foo', 'key2' => 'baz', 'value' => 3],
				['key1' => 'quux', 'key2' => 'baz', 'value' => 1],
			],
			['key1', 'key2'],
			'value'
		);

		$this->assertEquals(
			[
				'foo' => [
					'bar' => 8,
					'baz' => 3,
					null => 11
				],
				'quux' => [
					'bar' => 0,
					'baz' => 1,
					null => 1
				],
				null => [
					'bar' => 8,
					'baz' => 4,
					null => 12
				]
			],
			$result
		);
	}
}
