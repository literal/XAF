<?php
namespace XAF\web\session;

use PHPUnit\Framework\TestCase;
use Phake;

use XAF\web\session\Session;
use XAF\http\Request;
use XAF\web\UrlResolver;

/**
 * @covers \XAF\web\session\CsrfProtector
 */
class CsrfProtectorTest extends TestCase
{
	/** @var Session */
	private $sessionMock;

	/** @var Request */
	private $requestMock;

	/** @var UrlResolver */
	private $urlResolverMock;

	/** @var CsrfProtector */
	private $object;

	protected function setUp(): void
	{
		$this->sessionMock = Phake::mock(Session::class);
		$this->requestMock = Phake::mock(Request::class);
		$this->urlResolverMock = Phake::mock(UrlResolver::class);
		$this->object = new CsrfProtector($this->sessionMock, $this->requestMock, $this->urlResolverMock);
	}

	public function testQueryParamNameDefaultsToCt()
	{
		Phake::when($this->sessionMock)->isOpen()->thenReturn(true);

		$this->object->start();

		Phake::verify($this->urlResolverMock)->setAutoQueryParam('ct', $this->anything());
	}

	public function testCustomQueryParamNameCanBeSet()
	{
		Phake::when($this->sessionMock)->isOpen()->thenReturn(true);

		$this->object->setQueryParamName('foobar');

		$this->object->start();
		Phake::verify($this->urlResolverMock)->setAutoQueryParam('foobar', $this->anything());
	}

	public function testStartStoresTokenInSession()
	{
		Phake::when($this->sessionMock)->isOpen()->thenReturn(true);

		$this->object->start();

		Phake::verify($this->sessionMock)->setData('_csrfProtectionToken', $this->anything());
	}

	public function testStartDoesNothingWhenSessionIsNotOpen()
	{
		Phake::when($this->sessionMock)->isOpen()->thenReturn(false);

		$this->object->start();

		Phake::verify($this->sessionMock, Phake::never())->setData(Phake::anyParameters());
		Phake::verifyNoInteraction($this->urlResolverMock);
	}

	public function testCheckAndCarrySetsQueryParam()
	{
		Phake::when($this->sessionMock)->isOpen()->thenReturn(true);
		Phake::when($this->sessionMock)->getData('_csrfProtectionToken')->thenReturn('ABC123');

		$this->object->checkAndCarry();

		Phake::verify($this->urlResolverMock)->setAutoQueryParam('ct', 'ABC123');
	}

	public function testCheckAndCarryReturnTrueWhenIncorrectQueryParamWasReceived()
	{
		Phake::when($this->sessionMock)->isOpen()->thenReturn(true);
		Phake::when($this->sessionMock)->getData('_csrfProtectionToken')->thenReturn('ABC123');
		Phake::when($this->requestMock)->getQueryParam('ct')->thenReturn('ABC123');

		$result = $this->object->checkAndCarry();

		$this->assertTrue($result);
	}

	public function testCheckAndCarryReturnsFalseWhenIncorrectQueryParamWasReceived()
	{
		Phake::when($this->sessionMock)->isOpen()->thenReturn(true);
		Phake::when($this->sessionMock)->getData('_csrfProtectionToken')->thenReturn('ABC123');
		Phake::when($this->requestMock)->getQueryParam('ct')->thenReturn('XYZ789');

		$result = $this->object->checkAndCarry();

		$this->assertFalse($result);
	}

	public function testCheckAndCarryReturnsTrueWhenSessionIsClosed()
	{
		Phake::when($this->sessionMock)->isOpen()->thenReturn(false);

		$result = $this->object->checkAndCarry();

		$this->assertTrue($result);
	}

	public function testStopRemovesQueryParam()
	{
		Phake::when($this->sessionMock)->isOpen()->thenReturn(true);

		$this->object->stop();

		Phake::verify($this->urlResolverMock)->setAutoQueryParam('ct', null);
	}
}
