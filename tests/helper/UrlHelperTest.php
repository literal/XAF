<?php
namespace XAF\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\helper\UrlHelper
 */
class UrlHelperTest extends TestCase
{
	public function testBuildAbsoluteUrlPrependsDefaultBase()
	{
		$result = UrlHelper::buildAbsoluteUrl('http://www.foo.com/', '/foo');

		$this->assertEquals('http://www.foo.com/foo', $result);
	}

	public function testBuildAbsoluteUrlPreservesGivenAbsoluteUrl()
	{
		$result = UrlHelper::buildAbsoluteUrl('http://www.bar.com/', 'http://www.test.de/foo/?foo=bar');

		$this->assertEquals('http://www.test.de/foo/?foo=bar', $result);
	}

	public function testBuildQueryStringDropsNullAndFalseParams()
	{
		$result = UrlHelper::buildQueryString(['foo' => null, 'bar' => false]);

		$this->assertSame('', $result);
	}

	public function testBuildQueryStringPrependsQuestionMark()
	{
		$result = UrlHelper::buildQueryString(['foo' => 'bar']);

		$this->assertEquals('?', $result[0]);
	}

	public function testBuildQueryStringAddsScalarFieldsAsStrings()
	{
		$result = UrlHelper::buildQueryString(['foo' => 'bar', 'boom' => 8, 'quux' => true]);

		$this->assertEquals('?foo=bar&boom=8&quux=1', $result);
	}

	public function testBuildQueryStringEncodesArrayValuesInPhpFashion()
	{
		$result = UrlHelper::buildQueryString(['foo' => ['bar', ['boom' => 'quux']]]);

		$this->assertEquals('?foo%5B0%5D=bar&foo%5B1%5D%5Bboom%5D=quux', $result);
	}

	public function testBuildQueryIgnoresUnsupportedValueTypes()
	{
		// Only scalars and arrays/hashes can be used as values
		$result = UrlHelper::buildQueryString(['foo' => new \stdClass]);

		$this->assertSame('', $result);
	}

	public function testMergeQuerySetsParams()
	{
		$result = UrlHelper::mergeQuery('/foo', ['foo' => 'bar']);

		$this->assertEquals('/foo?foo=bar', $result);
	}

	public function testMergeQueryAppendsParams()
	{
		$result = UrlHelper::mergeQuery('/foo?bar=foo', ['foo' => 'bar']);

		$this->assertEquals('/foo?bar=foo&foo=bar', $result);
	}

	public function testMergeQueryOverwritesParams()
	{
		$result = UrlHelper::mergeQuery('/foo?foo=overwriteme', ['foo' => 'bar']);

		$this->assertEquals('/foo?foo=bar', $result);
	}

	public function testMergeQueryKeepsParamWithEmptyStringValue()
	{
		$result = UrlHelper::mergeQuery('/foo?foo=overwriteme', ['foo' => '']);

		$this->assertEquals('/foo?foo=', $result);
	}

	public function testMergeQueryDropsParamWithNullOrFalseValue()
	{
		$result = UrlHelper::mergeQuery('/foo?foo=deleteme', ['foo' => null, 'bar' => false]);

		$this->assertEquals('/foo', $result);
	}
}
