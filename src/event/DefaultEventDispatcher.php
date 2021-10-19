<?php
namespace XAF\event;

use XAF\di\DiContainer;

use XAF\exception\SystemError;

class DefaultEventDispatcher implements EventDispatcher
{
	/** @var DiContainer */
	protected $dc;

	/** @var array */
	protected $eventHandlerMap = [];

	public function __construct( DiContainer $dc )
	{
		$this->dc = $dc;
	}

	/**
	 * Format of the event handler map:
	 *
	 * {
	 *     // the event key is a string, preferably in "domain.event" format
	 *   <eventKey>: [
	 *     {
	 *         // Key of handling object, alias as known to the container
	 *       'handler': <objectAlias>,
	 *         // method name - one parameter will be passed, the parameter type is set by the event raiser
	 *       'method': <methodName>,
	 *
	 *         // optional, only call the handler if the object already exists in the container
	 *       'nocreate': <bool>
	 *     },
	 *     ...
	 *   ],
	 *   ...
	 * }
	 *
	 * @param array $map
	 */
	public function setEventHandlerMap( array $map )
	{
		$this->eventHandlerMap = $map;
	}

	/**
	 * @param string $eventKey
	 * @param string $objectKey
	 * @param string $methodName
	 * @param bool $noCreate
	 */
	public function addHandler( $eventKey, $objectKey, $methodName, $noCreate = false )
	{
		$this->eventHandlerMap[$eventKey][] = [
			'handler' => $objectKey,
			'method' => $methodName,
			'nocreate' => $noCreate
		];
	}

	/**
	 *
	 * @param string $eventKey
	 * @param string|null $objectKey all objects if null
	 * @param string|null $methodName all methods if null
	 */
	public function removeHandler( $eventKey, $objectKey = null, $methodName = null )
	{
		if( $objectKey === null && $methodName === null )
		{
			unset($this->eventHandlerMap[$eventKey]);
			return;
		}

		foreach( $this->eventHandlerMap[$eventKey] as $k => $target )
		{
			if( $objectKey === null || $objectKey == $target['handler']
				&& $methodName === null || $methodName == $target['method'] )
			{
				unset($this->eventHandlerMap[$eventKey][$k]);
			}
		}
	}

	/**
	 * @param string $eventKey string the event type key - preferably in "domain.event" format
	 * @param mixed ... any further arguments will be passed to the event handlers
	 */
	public function triggerEvent( $eventKey )
	{
		if( !isset($this->eventHandlerMap[$eventKey]) )
		{
			return;
		}

		foreach( $this->eventHandlerMap[$eventKey] as $target )
		{
			if( !isset($target['handler'], $target['method']) )
			{
				throw new SystemError('invalid event map entry', $target, 'event [' . $eventKey . ']');
			}
			if( isset($target['nocreate']) && $target['nocreate'] && !$this->dc->exists($target['handler']) )
			{
				continue;
			}
			$object = $this->dc->get($target['handler']);
			$arguments = \func_get_args();
			\array_shift($arguments);
			\call_user_func_array([$object, $target['method']], $arguments);
		}
	}
}

