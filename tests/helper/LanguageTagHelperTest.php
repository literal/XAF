<?php
namespace XAF\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\helper\LanguageTagHelper
 */
class LanguageTagHelperTest extends TestCase
{
	static public function getNormalizeTuples()
	{
		return [
			['', ''],
			['en', 'en'],
			['de_CH', 'de-ch'],
			[' zh-Hant_ CN ', 'zh-hant-cn']
		];
	}

	/**
	 * @dataProvider getNormalizeTuples
	 */
	public function testNormalize( $input, $expectedResult )
	{
		$result = LanguageTagHelper::normalize($input);

		$this->assertEquals($expectedResult, $result);
	}

	static public function getToObjectQualifierTuples()
	{
		return [
			['', ''],
			['en', '.en'],
			['de_CH', '.de.ch']
		];
	}

	/**
	 * @dataProvider getToObjectQualifierTuples
	 */
	public function testToObjectQualifier( $input, $expectedResult )
	{
		$result = LanguageTagHelper::toObjectQualifier($input);

		$this->assertEquals($expectedResult, $result);
	}

	static public function getFromObjectQualifierTuples()
	{
		return [
			['', ''],
			['en', 'en'],
			['de.ch', 'de-ch']
		];
	}

	/**
	 * @dataProvider getFromObjectQualifierTuples
	 */
	public function testFromObjectQualifier( $input, $expectedResult )
	{
		$result = LanguageTagHelper::fromObjectQualifier($input);

		$this->assertEquals($expectedResult, $result);
	}

	static public function getSplitTuples()
	{
		return [
			['', []],
			['en', ['en']],
			['de_CH', ['de', 'ch']]
		];
	}

	/**
	 * @dataProvider getSplitTuples
	 */
	public function testSplit( $input, $expectedResult )
	{
		$result = LanguageTagHelper::split($input);

		$this->assertEquals($expectedResult, $result);
	}
}
