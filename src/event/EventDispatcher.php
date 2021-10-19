<?php
namespace XAF\event;

interface EventDispatcher
{
	/**
	 * @param string $eventKey arbitrary event ID, preferably in "domain.event" format
	 * @param string $objectKey key by which the dispatcher can aquire the handling object
	 * @param string $methodName method to call on the handler object when the event is triggered
	 * @param bool $noCreate only call the handler if the object already exists
	 */
	public function addHandler( $eventKey, $objectKey, $methodName, $noCreate = false );

	/**
	 * @param string $eventKey
	 * @param string|null $objectKey all objects if null
	 * @param string|null $methodName all methods if null
	 */
	public function removeHandler( $eventKey, $objectKey = null, $methodName = null );

	/**
	 * @param string $eventKey string the event type key - preferably in "domain.event" format
	 * @param mixed ... any further arguments will be passed to the event handlers
	 */
	public function triggerEvent( $eventKey );
}

