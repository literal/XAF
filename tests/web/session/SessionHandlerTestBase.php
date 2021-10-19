<?php
namespace XAF\web\session;

use PHPUnit\Framework\TestCase;
use Phake;

use XAF\http\Request;

/**
 * @covers \XAF\web\session\SessionHandler
 */
abstract class SessionHandlerTestBase extends TestCase
{
	/** @var SessionHandler */
	protected $object;

	/** @var Session */
	protected $sessionMock;

	/** @var Request */
	protected $requestMock;

	protected function setUp(): void
	{
		$this->sessionMock = Phake::mock(Session::class);
		$this->requestMock = Phake::mock(Request::class);
	}

	// These set-up an verification methods must be implemented by the derived tests:

	abstract protected function setSessionTokenIsPresent( $token, $fieldName = 'st' );

	abstract protected function assertSessionTokenIsPropagated( $token, $fieldName = 'st' );

	abstract protected function assertSessionTokenIsNotPropagated();

	abstract protected function assertSessionTokenIsRemoved( $fieldName = 'st' );

	// =============================================================================================
	// startSession()
	// =============================================================================================

	public function testStartSessionStartsSessionAndPropagatesToken()
	{
		Phake::when($this->sessionMock)->getToken()->thenReturn('abc-123');
		Phake::when($this->sessionMock)->isOpen()->thenReturn(false)->thenReturn(true);
		Phake::when($this->sessionMock)->isNew()->thenReturn(true);

		$this->object->startSession();

		Phake::verify($this->sessionMock)->start();
		$this->assertSessionTokenIsPropagated('abc-123');
	}

	public function testStartSessionClosesExistingSessionIfAlreadyOpen()
	{
		Phake::when($this->sessionMock)->getToken()->thenReturn('abc-123');
		Phake::when($this->sessionMock)->isOpen()->thenReturn(true);

		$this->object->startSession();

		Phake::verify($this->sessionMock)->end();
	}

	// =============================================================================================
	// continueOrStartSession()
	// =============================================================================================

	public function testContinueOrStartSessionContinuesExistingSession()
	{
		$this->setSessionTokenIsPresent('abc-123');
		Phake::when($this->sessionMock)->isOpen()->thenReturn(false)->thenReturn(true);
		Phake::when($this->sessionMock)->isNew()->thenReturn(false);

		$this->object->continueOrStartSession();

		Phake::verify($this->sessionMock)->continueIfExists('abc-123');
		Phake::verify($this->sessionMock, Phake::never())->start();
	}

	public function testContinueOrStartSessionDoesNothingWhenSessionIsAlreadyOpen()
	{
		$this->setSessionTokenIsPresent('abc-123');
		Phake::when($this->sessionMock)->isNew()->thenReturn(false);
		Phake::when($this->sessionMock)->isOpen()->thenReturn(true);

		$this->object->continueOrStartSession();

		Phake::verify($this->sessionMock, Phake::never())->continueIfExists(Phake::anyParameters());
		Phake::verify($this->sessionMock, Phake::never())->start();
		$this->assertSessionTokenIsNotPropagated();
	}

	public function testContinueOrStartSessionStartsSessionWhenNoSessionTokenIsPresent()
	{
		Phake::when($this->sessionMock)->isOpen()->thenReturn(false);
		Phake::when($this->sessionMock)->isNew()->thenReturn(true);

		$this->object->continueOrStartSession();

		Phake::verify($this->sessionMock)->start();
	}

	public function testContinueOrStartSessionStartsSessionWhenSessionTokenIsNotValid()
	{
		$this->setSessionTokenIsPresent('abc-123');
		Phake::when($this->sessionMock)->isOpen()->thenReturn(false);
		Phake::when($this->sessionMock)->isNew()->thenReturn(true);

		$this->object->continueOrStartSession();

		Phake::verify($this->sessionMock)->start();
	}

	// =============================================================================================
	// continueSessionIfExists()
	// =============================================================================================

	public function testContinueSessionIfExistsTriesToContinueExistingSession()
	{
		Phake::when($this->sessionMock)->isOpen()->thenReturn(false);
		$this->setSessionTokenIsPresent('abc-123');

		$this->object->continueSessionIfExists();

		Phake::verify($this->sessionMock)->continueIfExists('abc-123');
	}

	public function testContinueSessionIfExistsDoesNothingWhenSessionIsAlreadyOpen()
	{
		Phake::when($this->sessionMock)->isOpen()->thenReturn(true);
		$this->setSessionTokenIsPresent('abc-123');

		$this->object->continueSessionIfExists();

		Phake::verify($this->sessionMock, Phake::never())->continueIfExists(Phake::anyParameters());
		$this->assertSessionTokenIsNotPropagated();
	}

	public function testContinueSessionIfExistsDoesNothingWhenNoSessionTokenExists()
	{
		Phake::when($this->sessionMock)->isOpen()->thenReturn(false);

		$this->object->continueSessionIfExists();

		Phake::verify($this->sessionMock, Phake::never())->continueIfExists(Phake::anyParameters());
		$this->assertSessionTokenIsNotPropagated();
	}

	public function testContinueSessionIfExistsDoesNothingWhenSessionTokenDoesNotBelongToAnExistingSession()
	{
		$this->setSessionTokenIsPresent('abc-123');
		Phake::when($this->sessionMock)->isOpen()->thenReturn(false);

		$this->object->continueSessionIfExists();

		Phake::verify($this->sessionMock, Phake::never())->start();
		$this->assertSessionTokenIsNotPropagated();
	}

	// =============================================================================================
	// endSession()
	// =============================================================================================

	public function testEndSessionClosesSessionAndDeletesToken()
	{
		$this->object->endSession();

		Phake::verify($this->sessionMock)->end();
		$this->assertSessionTokenIsRemoved();
	}

	// =============================================================================================
	// setPropagationFieldName()
	// =============================================================================================

	public function testCustomTokenFieldNameCanBeSet()
	{
		$this->object->setPropagationFieldName('custom');

		Phake::when($this->sessionMock)->getToken()->thenReturn('abc-123');
		Phake::when($this->sessionMock)->isOpen()->thenReturn(false);
		Phake::when($this->sessionMock)->isNew()->thenReturn(true);
		$this->object->startSession();
		$this->assertSessionTokenIsPropagated('abc-123', 'custom');

		$this->setSessionTokenIsPresent('abc-123', 'custom');
		Phake::when($this->sessionMock)->isOpen()->thenReturn(false);
		Phake::when($this->sessionMock)->isNew()->thenReturn(false);
		$this->object->continueSessionIfExists();
		Phake::verify($this->sessionMock)->continueIfExists('abc-123');

		$this->object->endSession();
		$this->assertSessionTokenIsRemoved('custom');
	}
}
