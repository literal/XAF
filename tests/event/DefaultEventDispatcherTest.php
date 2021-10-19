<?php
namespace XAF\event;

use PHPUnit\Framework\TestCase;

use XAF\di\DiContainer;
use stdClass;

require_once __DIR__ . '/stubs/DiContainerStub.php';

/**
 * @covers \XAF\event\DefaultEventDispatcher
 */
class DefaultEventDispatcherTest extends TestCase
{
	/** @var DefaultEventDispatcher */
	private $object;

	/** @var DiContainer */
	private $diContainerStub;

	/** @var stdClass */
	private $handlerMock;

	protected function setUp(): void
	{
		$this->handlerMock = $this->getMockBuilder(stdClass::class)->setMethods(['handleEvent'])->getMock();
		$this->diContainerStub = new DiContainerStub($this->handlerMock);
		$this->object = new DefaultEventDispatcher($this->diContainerStub);
	}

	public function testHandlerSetByMapIsCalledOnEvent()
	{
		$this->object->setEventHandlerMap([
			'eventKey' => [
				[
					'handler' => 'handlerMockKey',
					'method' => 'handleEvent'
				]
			]
		]);

		$this->setExcpectedEventHandlerCallCount(1);
		$this->object->triggerEvent('eventKey');
	}

	public function testHandlerSetByAddHandlerIsCalledOnEvent()
	{
		$this->object->addHandler('eventKey', 'handlerMockKey', 'handleEvent');

		$this->setExcpectedEventHandlerCallCount(1);
		$this->object->triggerEvent('eventKey');
	}

	public function testHandlerIsNotCalledAfterWholeEventKeyWasRemoved()
	{
		$this->object->setEventHandlerMap([
			'eventKey' => [
				[
					'handler' => 'handlerMockKey',
					'method' => 'handleEvent'
				]
			]
		]);

		$this->object->removeHandler('eventKey');

		$this->setExcpectedEventHandlerCallCount(0);
		$this->object->triggerEvent('eventKey');
	}

	public function testHandlerIsNotCalledAfterTargetedRemovalByObjectAlias()
	{
		$this->object->setEventHandlerMap([
			'eventKey' => [
				// DiContainerStub always returns the same handler object independent of
				// the requested key.
				// Thus, according to this map, the handler method would be called twice,
				// but only one after the call to removeHandler()
				[
					'handler' => 'handlerMockKey',
					'method' => 'handleEvent'
				],
				[
					'handler' => 'handlerMockAlias', // yields the same object as 'handlerMockKey'
					'method' => 'handleEvent'
				]
			]
		]);

		$this->object->removeHandler('eventKey', 'handlerMockAlias');

		$this->setExcpectedEventHandlerCallCount(1);
		$this->object->triggerEvent('eventKey');
	}

	public function testHandlerIsNotCalledForNonExistentObjectIfNoCreateFlagIsSet()
	{
		$this->object->setEventHandlerMap([
			'eventKey' => [
				[
					'handler' => 'missingObject', // The DiContainerStub will report this key as non-existent
					'method' => 'handleEvent',
					'nocreate' => true
				]
			]
		]);

		$this->setExcpectedEventHandlerCallCount(0);
		$this->object->triggerEvent('eventKey');
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testNonExistentMapThrowsNoException()
	{
		$this->object->triggerEvent('eventKey');
	}

	public function testInvalidEventMapEntryThrowsException()
	{
		$this->object->setEventHandlerMap([
			'eventKey' => [
				[
					'method' => null,
					'handler' => null,
				]
			]
		]);

		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('invalid event map entry');
		$this->object->triggerEvent('eventKey');
	}

	private function setExcpectedEventHandlerCallCount( $count )
	{
		$this->handlerMock
			->expects($this->exactly($count))
			->method('handleEvent');
	}
}
