<?php
namespace XAF\web\session;

use XAF\persist\Persistable;

class SessionPersistor
{
	/**
	 * @var Session
	 */
	protected $session;

	/**
	 * @var Persistable[]
	 */
	protected $objects = [];

	public function __construct( Session $session )
	{
		$this->session = $session;
	}

	/**
	 * import session data into a persistable object and add the object to the list
	 * of managed objects
	 *
	 * @param string $key session data key
	 * @param Persistable $object
	 */
	public function restoreObjectState( $key, Persistable $object )
	{
		if( $this->session->isOpen() )
		{
			$objectState = $this->session->getData($key);
			if( \is_array($objectState) )
			{
				$object->importState($objectState);
			}
		}
		$this->objects[$key] = $object;
	}

	/**
	 * Write all managed object's data to the session
	 */
	public function flush()
	{
		if( $this->session->isOpen() )
		{
			foreach( $this->objects as $key => $object )
			{
				$objectState = $object->exportState();
				$this->session->setData($key, $objectState);
			}
		}
	}
}
