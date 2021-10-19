<?php
namespace XAF\web;

use PHPUnit\Framework\TestCase;
use Phake;

use XAF\http\Request;

/**
 * @covers \XAF\web\DefaultUrlResolver
 */
class DefaultUrlResolverTest extends TestCase
{
	/** @var DefaultUrlResolver */
	private $object;

	/** @var Request */
	private $requestMock;

	protected function setUp(): void
	{
		$this->requestMock = Phake::mock(Request::class);
		$this->object = new DefaultUrlResolver($this->requestMock);
	}

	// =============================================================================================
	// Initialization
	// =============================================================================================

	public function testBaseUrl()
	{
		$this->object->setBaseUrl('https://www.domain.com/');

		$result = $this->object->getBaseUrl();

		$this->assertEquals('https://www.domain.com/', $result);
	}

	public function testBaseUrlIsNormalized()
	{
		$this->object->setBaseUrl('https://www.domain.com');

		$result = $this->object->getBaseUrl();

		$this->assertEquals('https://www.domain.com/', $result);
	}

	public function testRootPath()
	{
		$this->object->setRootPath('/root');

		$result = $this->object->getRootPath();

		$this->assertEquals('/root', $result);
	}

	public function testRootPathIsNormalized()
	{
		$this->object->setRootPath('/');

		$result = $this->object->getRootPath();

		$this->assertEquals('', $result);
	}

	public function testBasePath()
	{
		$this->object->setBasePath('/base');

		$result = $this->object->getBasePath();

		$this->assertEquals('/base', $result);
	}

	public function testBasePathIsNormalized()
	{
		$this->object->setBasePath('base//');

		$result = $this->object->getBasePath();

		$this->assertEquals('/base', $result);
	}

	public function testAutoQueryParams()
	{
		$this->object->setAutoQueryParam('foo', 'bar');

		$result = $this->object->getAutoQueryParams();

		$this->assertEquals(['foo' => 'bar'], $result);
	}

	// =============================================================================================
	// Current Request Infomation
	// =============================================================================================

	public function testCurrentPagePath()
	{
		$this->setupRequest('/current/path', ['foo' => 'bar']);

		$result = $this->object->getCurrentPagePath();

		$this->assertEquals('/current/path', $result);
	}

	public function testCurrentQueryParams()
	{
		$this->setupRequest('/current/path', ['foo' => 'bar']);

		$result = $this->object->getCurrentQueryParams();

		$this->assertEquals(['foo' => 'bar'], $result);
	}

	public function testCurrentPagePathWithQuery()
	{
		$this->setupRequest('/current/path', ['foo' => 'bar']);

		$result = $this->object->getCurrentPagePathWithQuery();

		$this->assertEquals('/current/path?foo=bar', $result);
	}

	// =============================================================================================
	// URL Construction
	// =============================================================================================

	public function testRootPathIsPrepended()
	{
		$this->object->setBaseUrl('https://www.domain.com/');
		$this->object->setRootPath('/root');

		$this->assertEquals('/root/somepath', $this->object->buildUrlPath('/somepath'));
		$this->assertEquals('https://www.domain.com/root/somepath', $this->object->buildAbsUrl('/somepath'));
		$this->assertEquals('/root/somepath', $this->object->buildHref('/somepath'));
		$this->assertEquals('https://www.domain.com/root/somepath', $this->object->buildAbsHref('/somepath'));
	}

	static public function relativePagePathDataProvider()
	{
		return [
			['page', '/page'],
			['', ''],
			['.', ''],
			['./', '/'],
			['./page', '/page'],
		];
	}

	/**
	 * @dataProvider relativePagePathDataProvider
	 */
	public function testBasePathIsUsedForRelativePagePaths( $pagePath, $expectedAfterBase )
	{
		$this->object->setBaseUrl('https://www.domain.com/');
		$this->object->setRootPath('/root');
		$this->object->setBasePath('/base');

		$this->assertEquals('/root/base' . $expectedAfterBase, $this->object->buildUrlPath($pagePath));
		$this->assertEquals('https://www.domain.com/root/base' . $expectedAfterBase, $this->object->buildAbsUrl($pagePath));
		$this->assertEquals('/root/base' . $expectedAfterBase, $this->object->buildHref($pagePath));
		$this->assertEquals('https://www.domain.com/root/base' . $expectedAfterBase, $this->object->buildAbsHref($pagePath));
	}

	public function testBasePathIsNotUsedForAbsolutePagePaths()
	{
		$this->object->setBaseUrl('https://www.domain.com/');
		$this->object->setRootPath('/root');
		$this->object->setBasePath('/base');

		$this->assertEquals('/root/page', $this->object->buildUrlPath('/page'));
		$this->assertEquals('https://www.domain.com/root/page', $this->object->buildAbsUrl('/page'));
		$this->assertEquals('/root/page', $this->object->buildHref('/page'));
		$this->assertEquals('https://www.domain.com/root/page', $this->object->buildAbsHref('/page'));
	}

	public function testQueryParamsAreAdded()
	{
		$this->object->setBaseUrl('https://www.domain.com/');

		$this->assertEquals('/page?foo=bar', $this->object->buildUrlPath('page', ['foo' => 'bar']));
		$this->assertEquals('https://www.domain.com/page?foo=bar', $this->object->buildAbsUrl('page', ['foo' => 'bar']));
		$this->assertEquals('/page?foo=bar', $this->object->buildHref('page', ['foo' => 'bar']));
		$this->assertEquals('https://www.domain.com/page?foo=bar', $this->object->buildAbsHref('page', ['foo' => 'bar']));
	}

	public function testQueryParamsAreUrlEncoded()
	{
		$this->object->setBaseUrl('https://www.domain.com/');

		$this->assertEquals('/page?f%3Doo=ba%25r', $this->object->buildUrlPath('page', ['f=oo' => 'ba%r']));
		$this->assertEquals('https://www.domain.com/page?f%3Doo=ba%25r', $this->object->buildAbsUrl('page', ['f=oo' => 'ba%r']));
		$this->assertEquals('/page?f%3Doo=ba%25r', $this->object->buildHref('page', ['f=oo' => 'ba%r']));
		$this->assertEquals('https://www.domain.com/page?f%3Doo=ba%25r', $this->object->buildAbsHref('page', ['f=oo' => 'ba%r']));
	}

	public function testQueryParamsAreMergedWithQueryInPagePath()
	{
		$this->object->setBaseUrl('https://www.domain.com/');

		$this->assertEquals('/page?boom=baz&foo=bar', $this->object->buildUrlPath('page?boom=baz', ['foo' => 'bar']));
		$this->assertEquals('https://www.domain.com/page?boom=baz&foo=bar', $this->object->buildAbsUrl('page?boom=baz', ['foo' => 'bar']));
		$this->assertEquals('/page?boom=baz&foo=bar', $this->object->buildHref('page?boom=baz', ['foo' => 'bar']));
		$this->assertEquals('https://www.domain.com/page?boom=baz&foo=bar', $this->object->buildAbsHref('page?boom=baz', ['foo' => 'bar']));
	}

	public function testAutoQueryParamsAreOnlyAddedToHref()
	{
		$this->object->setBaseUrl('https://www.domain.com/');
		$this->object->setAutoQueryParam('foo', 'bar');

		$this->assertEquals('/page', $this->object->buildUrlPath('page'));
		$this->assertEquals('https://www.domain.com/page', $this->object->buildAbsUrl('page'));

		$this->assertEquals('/page?foo=bar', $this->object->buildHref('page'));
		$this->assertEquals('https://www.domain.com/page?foo=bar', $this->object->buildAbsHref('page'));
	}

	public function testBuildHrefAutoQueryParamsOverrideUrlPathParams()
	{
		$this->object->setBaseUrl('https://www.domain.com/');
		$this->object->setAutoQueryParam('foo', 'auto');

		$this->assertEquals('/page?foo=auto', $this->object->buildHref('/page?foo=path'));
		$this->assertEquals('https://www.domain.com/page?foo=auto', $this->object->buildAbsHref('/page?foo=path'));
	}

	public function testBuildHrefExtraQueryParamsOverrideAutoQueryParams()
	{
		$this->object->setBaseUrl('https://www.domain.com/');
		$this->object->setAutoQueryParam('foo', 'auto');

		$this->assertEquals('/page?foo=override', $this->object->buildHref('/page', ['foo' => 'override']));
		$this->assertEquals('https://www.domain.com/page?foo=override', $this->object->buildAbsHref('/page', ['foo' => 'override']));
	}

	// =============================================================================================
	// URL Parsing
	// =============================================================================================

	public function testUrlPathToPagePathRemovesRootPath()
	{
		$this->object->setRootPath('/root');

		$result = $this->object->urlPathToPagePath('/root/somepath');

		$this->assertEquals('/somepath', $result);
	}

	public function testUrlPathToPagePathFailsIfUrlPathDoesNotStartWithRootPath()
	{
		$this->object->setRootPath('/root');

		$this->expectException(\XAF\Exception\SystemError::class);
		$this->object->urlPathToPagePath('/somepath');
	}

	// =============================================================================================
	// Test Setup
	// =============================================================================================

	private function setupRequest( $path = '/', $params = [] )
	{
		Phake::when($this->requestMock)->getRequestPath()->thenReturn($path);
		Phake::when($this->requestMock)->getQueryParams()->thenReturn($params);
	}
}
