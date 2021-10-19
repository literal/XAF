<?php
namespace XAF\di;

use ArrayAccess;
use XAF\di\DiContainer;
use XAF\exception\SystemError;

/**
 * Wrapper for the DiContainer, providing read-only access with ArrayAccess.
 *
 * Access is restricted to the wrapped DiContainer's local objects.
 * I.e. objects from the container's parent containers (if any) are not handed out.
 */
class Locator implements ArrayAccess
{
	/** @var DiContainer */
	protected $container;

	/** @var string appended to all object keys when requesting objects from the container */
	protected $objectQualifier = '';

	public function __construct( DiContainer $container, $objectQualifier = '' )
	{
		$this->container = $container;
		$this->objectQualifier = $objectQualifier ? '.' . $objectQualifier : '';
	}

	/**
	 * Get all supported object aliases - this is not exactly the same as all available element offsets,
	 * because trailing object qualifiers may be appended to the object aliases ("alias.qualifier").
	 *
	 * return array
	 */
	public function getAllObjectAliases()
	{
		return $this->container->getAllLocalObjectAliases();
	}

	// ************************************************************************
	// Implementation of ArrayAccess
	// ************************************************************************

	public function offsetExists( $offset )
	{
		return $this->container->isKnownLocally($offset . $this->objectQualifier);
	}

	public function offsetGet( $offset )
	{
		return $this->container->getLocal($offset . $this->objectQualifier);
	}

	public function offsetSet( $offset, $value )
	{
		throw new SystemError('object cannot be set', $offset);
	}

	public function offsetUnset( $offset )
	{
		throw new SystemError('object cannot be unset', $offset);
	}
}
