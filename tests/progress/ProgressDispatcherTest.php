<?php
namespace XAF\progress;

use PHPUnit\Framework\TestCase;

use XAF\type\Message;

require_once __DIR__ . '/stubs/ListenerStub.php';

/**
 * @covers \XAF\progress\ProgressDispatcher
 */
class ProgressDispatcherTest extends TestCase
{
	/** @var ProgressDispatcher */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new ProgressDispatcher();
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testNoListenerIsRequired()
	{
		$this->object->step('Computing foo');
	}

	public function testMessageIsPassedToMultipleListeners()
	{
		$listener1 = $this->createAndAddListenerStub();
		$listener2 = $this->createAndAddListenerStub();

		$this->object->step('Computing foo');

		$this->assertMessageWasReceived($listener1);
		$this->assertMessageWasReceived($listener2);
	}

	public function testListenerRemoval()
	{
		$listener1 = $this->createAndAddListenerStub();
		$listener2 = $this->createAndAddListenerStub();

		$this->object->removeListener($listener1);
		$this->object->step('Computing foo');

		$this->assertNoMessageWasReceived($listener1);
		$this->assertMessageWasReceived($listener2);
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testRemovalOfNonAddedListenerCausesNoError()
	{
		$listener = new ListenerStub;

		$this->object->removeListener($listener);
	}

	public function testSectionHead()
	{
		$listener = $this->createAndAddListenerStub();

		$this->object->section('Fooing all the bars');

		$this->assertMessageWasReceived($listener, 'SectionHead', 'Fooing all the bars');
	}

	public function testStep()
	{
		$listener = $this->createAndAddListenerStub();

		$this->object->step('Computing foo');

		$this->assertMessageWasReceived($listener, 'Message', 'Computing foo', [], Message::STATUS_NONE);
	}

	public function testWarning()
	{
		$listener = $this->createAndAddListenerStub();

		$this->object->warning('Bar not found', ['searchPath' => 'goo']);

		$this->assertMessageWasReceived(
			$listener,
			'Message',
			'Bar not found',
			['searchPath' => 'goo'],
			Message::STATUS_WARNING
		);
	}

	public function testError()
	{
		$listener = $this->createAndAddListenerStub();

		$this->object->error('Foo failed', ['id' => 17]);

		$this->assertMessageWasReceived(
			$listener,
			'Message',
			'Foo failed',
			['id' => 17],
			Message::STATUS_ERROR
		);
	}

	public function testFailure()
	{
		$listener = $this->createAndAddListenerStub();

		$this->object->failure('Aborted foo - nothing was changed');

		$this->assertMessageWasReceived(
			$listener,
			'Conclusion',
			'Aborted foo - nothing was changed',
			null,
			Message::STATUS_ERROR
		);
	}

	public function testSuccess()
	{
		$listener = $this->createAndAddListenerStub();

		$this->object->success('Foo completed successfully', ['time' => 123]);

		$this->assertMessageWasReceived(
			$listener,
			'Conclusion',
			'Foo completed successfully',
			['time' => 123],
			Message::STATUS_SUCCESS
		);
	}

	public function testTick()
	{
		$listener = $this->createAndAddListenerStub();

		$this->object->tick();

		$this->assertMessageWasReceived($listener, 'Tick');
	}

	/**
	 * @return ListenerStub
	 */
	private function createAndAddListenerStub()
	{
		$listener = new ListenerStub;
		$this->object->addListener($listener);
		return $listener;
	}

	private function assertMessageWasReceived(
		ListenerStub $listener,
		$messageClass = null,
		$text = null,
		$params = null,
		$status = null
	)
	{
		$this->assertNotEmpty($listener->messages);

		$message = $listener->messages[0];

		if( $messageClass == 'Message' )
		{
			$this->assertInstanceOf('\\XAF\\type\\Message', $message);
		}
		else if( $messageClass !== null )
		{
			$this->assertInstanceOf('\\XAF\\progress\\' . $messageClass, $message);
		}
		if( $text !== null )
		{
			$this->assertEquals($text, $message->getText());
		}
		if( $params !== null )
		{
			$this->assertEquals($params, $message->getParams());
		}
		if( $status !== null )
		{
			$this->assertEquals($status, $message->getStatus());
		}
	}

	private function assertNoMessageWasReceived( ListenerStub $listener )
	{
		$this->assertEmpty($listener->messages);
	}
}
