<?php
namespace XAF\view\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\view\helper\UrlHelper
 */
class UrlHelperTest extends TestCase
{
	/** @var UrlHelper */
	private $object;

	protected function setUp(): void
	{
		$this->object = new UrlHelper();
	}

	public function testAddQueryParamsKeepsUrlUnchangedWhenNoQueryParamsAreProvided()
	{
		$this->assertEquals('http://foo.com/path', $this->object->addQueryParams('http://foo.com/path'));
	}

	public function testAddQueryParamsUrlEncodesParamNamesAndValues()
	{
		$this->assertEquals(
			'http://foo.com/path?a%2F=+b',
			$this->object->addQueryParams('http://foo.com/path', ['a/' => ' b'])
		);
	}

	public function testAddQueryParamsMergesQueryParamsWithQueryInPath()
	{
		$this->assertEquals(
			'http://foo.com/path?foo=bar&boom=baz',
			$this->object->addQueryParams('http://foo.com/path?foo=bar', ['boom' => 'baz'])
		);
	}

	public function testBuildAbsoluteUrlNormalizesSlashes()
	{
		$this->assertEquals('http://foo.com/path', $this->object->buildAbsoluteUrl('http://foo.com', 'path'));
		$this->assertEquals('http://foo.com/path', $this->object->buildAbsoluteUrl('http://foo.com/', '/path'));
	}

	public function testBuildAbsoluteUrlLeavesAlreadyAbsoluteUrlUnchanges()
	{
		$this->assertEquals(
			'http://bar.com/path',
			$this->object->buildAbsoluteUrl('http://foo.com', 'http://bar.com/path')
		);
	}
}
