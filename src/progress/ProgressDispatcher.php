<?php
namespace XAF\progress;

use XAF\type\Message;

/**
 * Dispatches progress messages to all registered listeners.
 */
class ProgressDispatcher implements Listener
{
	/** @var Listener[] */
	protected $listeners = [];

	public function addListener( Listener $listener )
	{
		$this->listeners[] = $listener;
	}

	public function removeListener( Listener $listener )
	{
		$index = $this->getListenerIndex($listener);
		if( $index !== null )
		{
			\array_splice($this->listeners, $index, 1);
		}
	}

	/**
	 * @param Listener $requestedListener
	 * @return int|null
	 */
	protected function getListenerIndex( Listener $requestedListener )
	{
		foreach( $this->listeners as $index => $existingListener )
		{
			if( $requestedListener === $existingListener )
			{
				return $index;
			}
		}
		return null;
	}

	/**
	 * Notify listeners that a new (super-)section has begin. Will be a heading for all following sections and items.
	 *
	 * @param string $name
	 * @param array $params
	 */
	public function heading( $name, array $params = [] )
	{
		$this->notify(new SectionHead($name, $params, 1));
	}

	/**
	 * Notify listeners that a new (sub-)section of the current operation has begin. The name will be
	 * a heading for the following items.
	 *
	 * @param string $name
	 * @param array $params
	 */
	public function section( $name, array $params = [] )
	{
		$this->notify(new SectionHead($name, $params, 2));
	}

	/**
	 * Notify listeners that a new step of the current operation is processed.
	 *
	 * @param string $message
	 * @param array $params
	 */
	public function step( $message, array $params = [] )
	{
		$this->notify(new Message($message, $params));
	}

	/**
	 * @param string $message
	 * @param array $params
	 */
	public function warning( $message, array $params = [] )
	{
		$this->notify(new Message($message, $params, Message::STATUS_WARNING));
	}

	/**
	 * @param string $message
	 * @param array $params
	 */
	public function error( $message, array $params = [] )
	{
		$this->notify(new Message($message, $params, Message::STATUS_ERROR));
	}

	/**
	 * @param string $message
	 * @param array $params
	 */
	public function failure( $message, array $params = [] )
	{
		$this->notify(new Conclusion($message, $params, Message::STATUS_ERROR));
	}

	/**
	 * @param string $message
	 * @param array $params
	 */
	public function success( $message, array $params = [] )
	{
		$this->notify(new Conclusion($message, $params, Message::STATUS_SUCCESS));
	}

	/**
	 * Post extended internal info which may be ignored by listeners
	 *
	 * @param string $message
	 * @param array $params
	 */
	public function debug( $message, array $params = [] )
	{
		$this->notify(new DebugInfo($message, $params));
	}

	/**
	 * Post unspecified progress message. Indicates that some piece of a lengthy operation has been done.
	 * The listeners might ignore this or move a progress bar forward, print a dot or whatever.
	 */
	public function tick()
	{
		$this->notify(new Tick());
	}

	public function notify( Message $message )
	{
		foreach( $this->listeners as $listener )
		{
			$listener->notify($message);
		}
	}
}
