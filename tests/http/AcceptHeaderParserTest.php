<?php
namespace XAF\http;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\http\AcceptHeaderParser
 */
class AcceptHeaderParserTest extends TestCase
{
	public function testItemsWithoutWeightsKeepOriginalOrder()
	{
		$result = AcceptHeaderParser::parse('foo,bar,boom,baz');

		$this->assertEquals(['foo', 'bar', 'boom', 'baz'], $result);
	}

	public function testWhitespaceAndParamsAreIgnored()
	{
		$result = AcceptHeaderParser::parse('foo ,  bar ; ignore this ');

		$this->assertEquals(['foo', 'bar'], $result);
	}

	public function testQValueSetsOrder()
	{
		$result = AcceptHeaderParser::parse('foo, bar; q=0.1, boom; q=0, baz; q=0.9');

		$this->assertEquals(['foo', 'baz', 'bar', 'boom'], $result);
	}

	public function testInvalidQValuesAreIgnored()
	{
		$result = AcceptHeaderParser::parse('foo; Q=0.1, bar; q=x, boom; q=20');

		$this->assertEquals(['foo', 'bar', 'boom'], $result);
	}

	public function testExtraParamsAndWhitespaceDoNotDisturbQvalue()
	{
		$result = AcceptHeaderParser::parse('foo; q = 0.1, bar; q=0.2; level=99, boom; x=20; q =.4');

		$this->assertEquals(['boom', 'bar', 'foo'], $result);
	}
}
