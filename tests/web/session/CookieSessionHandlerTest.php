<?php
namespace XAF\web\session;

use Phake;

use XAF\web\CookieSetter;

require_once __DIR__ . '/SessionHandlerTestBase.php';

/**
 * @covers \XAF\web\session\CookieSessionHandler
 * @covers \XAF\web\session\SessionHandler
 */
class CookieSessionHandlerTest extends SessionHandlerTestBase
{
	/** @var CookieSessionHandler */
	protected $object;

	/** @var CookieSetter */
	private $cookieSetterMock;

	protected function setUp(): void
	{
		parent::setUp();
		$this->cookieSetterMock = Phake::mock(CookieSetter::class);
		$this->object = new CookieSessionHandler($this->sessionMock, $this->requestMock, $this->cookieSetterMock);
	}

	protected function setSessionTokenIsPresent( $token, $fieldName = 'st' )
	{
		Phake::when($this->requestMock)->getCookie($fieldName)->thenReturn($token);
	}

	protected function assertSessionTokenIsPropagated( $token, $fieldName = 'st' )
	{
		Phake::verify($this->cookieSetterMock)->setSessionCookie($fieldName, $token);
	}

	protected function assertSessionTokenIsNotPropagated()
	{
		Phake::verifyNoInteraction($this->cookieSetterMock);
	}

	protected function assertSessionTokenIsRemoved( $fieldName = 'st' )
	{
		Phake::verify($this->cookieSetterMock)->deleteCookie($fieldName);
	}

	public function testCookieIsNotSetWhenSessionAlreadyExists()
	{
		$this->setSessionTokenIsPresent('1x2x3');
		Phake::when($this->sessionMock)->isOpen()->thenReturn(false)->thenReturn(true);
		Phake::when($this->sessionMock)->isNew()->thenReturn(false);
		Phake::when($this->sessionMock)->getToken()->thenReturn('1x2x3');

		$this->object->continueSessionIfExists();

		$this->assertSessionTokenIsNotPropagated();
	}
}
