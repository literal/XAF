<?php
namespace XAF\web\session;

use Phake;

use XAF\web\UrlResolver;

require_once __DIR__ . '/SessionHandlerTestBase.php';

/**
 * @covers \XAF\web\session\QueryParamSessionHandler
 * @covers \XAF\web\session\SessionHandler
 */
class QueryParamSessionHandlerTest extends SessionHandlerTestBase
{
	/** @var QueryParamSessionHandler */
	protected $object;

	/** @var UrlResolver */
	private $urlResolverMock;

	protected function setUp(): void
	{
		parent::setUp();
		$this->urlResolverMock = Phake::mock(UrlResolver::class);
		$this->object = new QueryParamSessionHandler($this->sessionMock, $this->requestMock, $this->urlResolverMock);
	}

	protected function setSessionTokenIsPresent( $token, $fieldName = 'st' )
	{
		Phake::when($this->requestMock)->getQueryParam($fieldName)->thenReturn($token);
	}

	protected function assertSessionTokenIsPropagated( $token, $fieldName = 'st' )
	{
		Phake::verify($this->urlResolverMock)->setAutoQueryParam($fieldName, $token);
	}

	protected function assertSessionTokenIsNotPropagated()
	{
		Phake::verifyNoInteraction($this->urlResolverMock);
	}

	protected function assertSessionTokenIsRemoved( $fieldName = 'st' )
	{
		Phake::verify($this->urlResolverMock)->setAutoQueryParam($fieldName, null);
	}

	public function testQueryParamIsAlsoSetWhenSessionAlreadyExists()
	{
		$this->setSessionTokenIsPresent('1x2x3');
		Phake::when($this->sessionMock)->isOpen()->thenReturn(false)->thenReturn(true);
		Phake::when($this->sessionMock)->isNew()->thenReturn(false);
		Phake::when($this->sessionMock)->getToken()->thenReturn('1x2x3');

		$this->object->continueSessionIfExists();

		$this->assertSessionTokenIsPropagated('1x2x3');
	}
}
