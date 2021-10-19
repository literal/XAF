<?php
namespace XAF\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\helper\JavascriptHelper
 */
class JavascriptHelperTest extends TestCase
{
	public function testStringLiteralIsQuotedAndEscaped()
	{
		$result = JavascriptHelper::buildLiteral('foo"');

		$this->assertEquals('"foo\\""', $result);
	}

	public function testEmptyStringBecomesEmptyString()
	{
		$result = JavascriptHelper::buildLiteral('');

		$this->assertEquals('""', $result);
	}

	public function testNumberLiteralIsNotQuoted()
	{
		$result = JavascriptHelper::buildLiteral(351);

		$this->assertEquals('351', $result);
	}

	public function testScalarArrayBecomesArrayLiteral()
	{
		$result = JavascriptHelper::buildLiteral(['foo', 'bar', 8]);

		$this->assertEquals('["foo","bar",8]', $result);
	}

	public function testHashBecomesObjectLiteralWithQuotedKeys()
	{
		$result = JavascriptHelper::buildLiteral(['foo' => 'foo']);

		$this->assertEquals('{"foo":"foo"}', $result);
	}

	public function testNestedStructure()
	{
		$result = JavascriptHelper::buildLiteral(
			[
				['foo' => 'bar'],
				21
			]
		);

		$this->assertEquals('[{"foo":"bar"},21]', $result);
	}

	public function testNullBecomesNull()
	{
		$result = JavascriptHelper::buildLiteral(null);

		$this->assertEquals('null', $result);
	}
}
