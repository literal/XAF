<?php
namespace XAF\di;

use XAF\exception\SystemError;

/**
 * Manage instances of model-, infrastructure- and helper-classes and make them available
 * to client objects.
 *
 * This is the primary means of dependency decoupling in the framework.
 *
 * All managed instances are known via an alias (a string).
 *
 * The Container can use a Factory to lazily create the requested objects upon
 * first access. Alternatively, existing objects can be registered from the outside.
 *
 * Multiple containers can be cascaded to provide specialized objects visible only to a
 * limited number of client objects while maintaining access to the objects of a parent container
 * which handles a broarder scope.
 */
class DefaultContainer implements DiContainer
{
	/**
	 * @var array {<key>: <object>, ...}
	 */
	protected $objectRegistry = [];

	/**
	 * @var DiContainer optional parent providing objects not known in this container
	 */
	protected $parentContainer;

	/**
	 * @var Factory
	 */
	protected $factory;

	/**
	 * @param DiContainer $parentContainer
	 */
	public function setParentContainer( DiContainer $parentContainer )
	{
		$this->parentContainer = $parentContainer;
	}

	// ************************************************************************
	// Implementation of DiContainer
	// ************************************************************************

	/**
	 * @param Factory $factory
	 */
	public function setFactory( Factory $factory )
	{
		$this->factory = $factory;
	}

	/**
	 * @return DiContainer
	 */
	public function createChildContainer()
	{
		$childContainer = new self();
		$childContainer->setParentContainer($this);
		return $childContainer;
	}

	/**
	 * @param string $key object alias
	 * @param object $object
	 */
	public function set( $key, $object )
	{
		$this->objectRegistry[$key] = $object;
	}

	/**
	 * @param string $key object alias
	 * @return object
	 */
	public function get( $key )
	{
		switch( true )
		{
			case $this->existsLocally($key):
				return $this->objectRegistry[$key];

			case $this->canCreateObject($key):
				$object = $this->createObject($key);
				$this->set($key, $object);
				return $object;

			case isset($this->parentContainer):
				return $this->parentContainer->get($key);
		}

		$this->throwUnknownObject($key);
	}

	/**
	 * @param string $key object alias
	 * @return object
	 */
	public function getLocal( $key )
	{
		switch( true )
		{
			case $this->existsLocally($key):
				return $this->objectRegistry[$key];

			case $this->canCreateObject($key):
				$object = $this->createObject($key);
				$this->set($key, $object);
				return $object;
		}

		$this->throwUnknownObject($key);
	}

	/**
	 * @param string $key
	 * @return object
	 * @throws SystemError if requested object not creatable
	 */
	public function create( $key )
	{
		switch( true )
		{
			case $this->canCreateObject($key):
				return $this->createObject($key);

			case isset($this->parentContainer):
				return $this->parentContainer->create($key);
		}

		$this->throwUnknownObject($key);
	}

	/**
	 * @param string $key
	 * @return object
	 * @throws SystemError if requested object not creatable
	 */
	public function createLocal( $key )
	{
		if( $this->canCreateObject($key) )
		{
			return $this->createObject($key);
		}

		$this->throwUnknownObject($key);
	}

	/**
	 * Create an object through the factory
	 *
	 * @param string $key
	 * @return object
	 */
	protected function createObject( $key )
	{
		return $this->factory->createObject($key, $this);
	}

	/**
	 * @param string $key
	 * @throws SystemError
	 */
	protected function throwUnknownObject( $key )
	{
		throw new SystemError('unknown object key', $key);
	}

	/**
	 * @return array
	 */
	public function getAllLocalObjectAliases()
	{
		$result = $this->factory->getCreatableObjectAliases();
		foreach( \array_keys($this->objectRegistry) as $exitentObjectKey )
		{
			$objectAlias = \explode('.', $exitentObjectKey, 2)[0];
			if( !\in_array($objectAlias, $result) )
			{
				$result[] = $objectAlias;
			}
		}
		return $result;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function isKnown( $key )
	{
		return $this->isKnownLocally($key) || $this->isKnownInParent($key);
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function  isKnownLocally( $key )
	{
		return $this->exists($key) || $this->canCreateObject($key);
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	protected function isKnownInParent( $key )
	{
		return isset($this->parentContainer) && $this->parentContainer->isKnown($key);
	}

	/**
	 * @param string $key object alias
	 * @return bool
	 */
	public function exists( $key )
	{
		return $this->existsLocally($key) || $this->existsInParent($key);
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function existsLocally( $key )
	{
		return isset($this->objectRegistry[$key]);
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	protected function existsInParent( $key )
	{
		return isset($this->parentContainer) && $this->parentContainer->exists($key);
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	protected function canCreateObject( $key )
	{
		return isset($this->factory) && $this->factory->canCreateObject($key);
	}
}
