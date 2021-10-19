<?php
namespace XAF\web\infilter;

use PHPUnit\Framework\TestCase;
use Phake;

use XAF\type\ParamHolder;
use XAF\type\ArrayParamHolder;
use XAF\http\Request;
use XAF\web\UrlResolver;

/**
 * @covers \XAF\web\infilter\QueryStringLanguageFilter
 * @covers \XAF\web\infilter\LanguageFilterBase
 */
class QueryStringLanguageFilterTest extends TestCase
{
	/** @var QueryStringLanguageFilter */
	private $object;

	/** @var Request */
	private $requestMock;

	/** @var ParamHolder */
	private $requestVars;

	/** @var UrlResolver */
	private $urlResolverMock;

	protected function setUp(): void
	{
		$this->requestMock = Phake::mock(Request::class);
		$this->requestVars = new ArrayParamHolder();
		$this->urlResolverMock = Phake::mock(UrlResolver::class);
	}

	public function testValidLanguageTagFromQueryStringIsSetAsRequestVarAndForwardedAsAutoQueryParam()
	{
		$this->setupObjectWithAvailableLanguages(['en', 'de', 'it']);
		$this->setQueryParamIs('lang', 'de');

		$this->object->execute();

		$this->assertEquals('de', $this->requestVars->get('language'));
		Phake::verify($this->urlResolverMock)->setAutoQueryParam('lang', 'de');
	}

	public function testInvalidLanguageTagFromQueryStringCausesRedirect()
	{
		$this->setupObjectWithAvailableLanguages(['en', 'de', 'it']);
		$this->setQueryParamIs('lang', 'fr');

		$this->expectException(\XAF\web\exception\HttpSelfRedirect::class);
		$this->object->execute();
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testInvalidLanguageTagFromQueryRedirectCanBeDisabled()
	{
		$this->setupObjectWithAvailableLanguages(['en', 'de', 'it']);
		$this->setQueryParamIs('lang', 'fr');

		$this->object->setParam('redirect', false);

		$this->object->execute();
	}

	public function testInvalidLanguageTagFromQueryStringCausesFirstAvailableLanguageToBeSet()
	{
		$this->setupObjectWithAvailableLanguages(['en', 'de', 'it']);
		$this->setQueryParamIs('lang', 'fr');
		$this->object->setParam('redirect', false);

		$this->object->execute();

		$this->assertEquals('en', $this->requestVars->get('language'));
		Phake::verify($this->urlResolverMock)->setAutoQueryParam('lang', 'en');
	}

	public function testNoLanguageTagInQueryStringCausesRedirect()
	{
		$this->setupObjectWithAvailableLanguages(['en', 'de', 'it']);

		$this->expectException(\XAF\web\exception\HttpSelfRedirect::class);
		$this->object->execute();
	}

	public function testNoLanguageTagInQueryStringCausesFirstAvailableLanguageToBeSet()
	{
		$this->setupObjectWithAvailableLanguages(['en', 'de', 'it']);
		$this->object->setParam('redirect', false);

		$this->object->execute();

		$this->assertEquals('en', $this->requestVars->get('language'));
		Phake::verify($this->urlResolverMock)->setAutoQueryParam('lang', 'en');
	}

	public function testDefaultLanguageIsSetWithOnlyOneAvailableLanguage()
	{
		$this->setupObjectWithAvailableLanguages(['tr']);

		$this->object->execute();

		$this->assertEquals('tr', $this->requestVars->get('language'));
	}

	public function testThereIsNoRedirectAndNoAutoQueryParamWithOnlyOneAvailableLanguage()
	{
		$this->setupObjectWithAvailableLanguages(['tr']);

		$this->object->execute();

		Phake::verify($this->urlResolverMock, Phake::never())->setAutoQueryParam(Phake::anyParameters());
	}

	public function testRedirectCanBeForcedWithOnlyOneAvailableLanguage()
	{
		$this->setupObjectWithAvailableLanguages(['tr']);

		$this->object->setParam('forceQueryParam', true);

		$this->expectException(\XAF\web\exception\HttpSelfRedirect::class);
		$this->object->execute();
	}


	public function testNoDefaultLanguageIsSetWithNoAvailableLanguage()
	{
		$this->setupObjectWithAvailableLanguages([]);

		$this->object->execute();

		$this->assertNull($this->requestVars->get('language'));
	}

	public function testThereIsNoRedirectAndNoAutoQueryParamWithNoAvailableLanguage()
	{
		$this->setupObjectWithAvailableLanguages([]);

		$this->object->execute();

		Phake::verify($this->urlResolverMock, Phake::never())->setAutoQueryParam(Phake::anyParameters());
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testRedirectCannotBeForcedWithNoAvailableLanguage()
	{
		$this->setupObjectWithAvailableLanguages([]);

		$this->object->setParam('forceQueryParam', true);

		$this->object->execute();
	}

	public function testFallbackToHttpAcceptHeaderIsUsedWithoutLanguageQueryParam()
	{
		$this->setupObjectWithAvailableLanguages(['es', 'it']);
		// 'it' has higher preference value than 'es':
		$this->setClientHttpAcceptLanguageHeadersValueIs('en,es;q=0.6,it;q=0.8');
		$this->object->setParam('redirect', false);

		$this->object->execute();

		$this->assertEquals('it', $this->requestVars->get('language'));
	}

	public function testDefaultLanguageIsSetWhenHttpAcceptHeaderMatchesNoAvailableLanguage()
	{
		$this->setupObjectWithAvailableLanguages(['es', 'it']);
		$this->setClientHttpAcceptLanguageHeadersValueIs('en-gb,en');
		$this->object->setParam('redirect', false);

		$this->object->execute();

		$this->assertEquals('es', $this->requestVars->get('language'));
	}

	public function testRedirectIsIssuedWhenLanguageIsSetFromHttpAcceptHeader()
	{
		$this->setupObjectWithAvailableLanguages(['de', 'en']);
		$this->setClientHttpAcceptLanguageHeadersValueIs('de,it');

		$this->expectException(\XAF\web\exception\HttpSelfRedirect::class);
		$this->object->execute();
	}

	public function testCustomQueryParamNameCanBeSet()
	{
		$this->setupObjectWithAvailableLanguages(['en', 'de', 'it']);
		$this->setQueryParamIs('lang', 'en');
		$this->setQueryParamIs('frobb', 'it');

		$this->object->setParam('queryParam', 'frobb');
		$this->object->execute();

		$this->assertEquals('it', $this->requestVars->get('language'));
	}

	public function testCustomTargetRequestVarNameCanBeSet()
	{
		$this->setupObjectWithAvailableLanguages(['en', 'de', 'it']);
		$this->setQueryParamIs('lang', 'en');

		$this->object->setParam('targetVar', 'patz');
		$this->object->execute();

		$this->assertEquals('en', $this->requestVars->get('patz'));
	}


	private function setupObjectWithAvailableLanguages( array $availableLanguages )
	{
		$this->object = new QueryStringLanguageFilter(
			$this->requestMock,
			$this->requestVars,
			$this->urlResolverMock,
			$availableLanguages
		);
	}

	private function setQueryParamIs( $name, $value )
	{
		Phake::when($this->requestMock)->getQueryParam($name)->thenReturn($value);
	}

	private function setClientHttpAcceptLanguageHeadersValueIs( $header )
	{
		Phake::when($this->requestMock)->getHeader('Accept-Language')->thenReturn($header);
	}
}
