<?php
namespace XAF\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\helper\SlugGenerator
 */
class SlugGeneratorTest extends TestCase
{
	static public function getTestTuples()
	{
		return [
			['foo and bar', 'foo-and-bar'], // Spaces are replaced
			['foo, bar (and: boom/baz)', 'foo-bar-and-boom-baz'], // All interpunction treated like space
			['foo-._()bar', 'foo-bar'], // Multiple non-word characters are compacted into a single underscore
			['Upper UPPER', 'upper-upper'], // Everything is lower-cased
			['-foo/}=', 'foo'], // leading and trailing non-word characters are removed
			['Äöß', 'aeoess'], // Special chars are transliterated
		];
	}

	/**
	 * @dataProvider getTestTuples
	 */
	public function testCreateSlug( $input, $expectedOutput )
	{
		$result = SlugGenerator::generateSlug($input);

		$this->assertEquals($expectedOutput, $result);
	}

	public function testDifferentWordSeparator()
	{
		$result = SlugGenerator::generateSlug('foo bar', '_');

		$this->assertEquals('foo_bar', $result);
	}
}
