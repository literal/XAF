<?php
namespace XAF\di;

use XAF\exception\SystemError;

interface DiContainer
{
	// @_todo:style segregate first two methods into detached or extended interface

	/**
	 * @param Factory $factory
	 */
	public function setFactory( Factory $factory );

	/**
	 * Create a new container instance to be used in a local context
	 *
	 * The child container will provide all of it's parent's objects unchanged, but
	 * objects added to it via set() or created by it's factory (if any)
	 * will only be available locally.
	 *
	 * @return DiContainer
	 */
	public function createChildContainer();

	/**
	 * Inject an existing object from the outside
	 *
	 * @param string $key
	 * @param object $object
	 */
	public function set( $key, $object );

	/**
	 * Retrieve or create object by it's alias - the object will be shared: every subsequent call
	 * to get() will return the same instance for the given key.
	 *
	 * If a parent container exists and the object is neither existent not creatable locally, the
	 * parent container will be asked for the object.
	 *
	 * @param string $key
	 * @return object
	 * @throws SystemError if requested object neither existent nor creatable
	 */
	public function get( $key );

	/**
	 * Retrieve or create object by it's key - the object will be shared: every subsequent call
	 * to get() or getLocally() will return the same instance for the given key.
	 *
	 * Parent containers - if any - will not be accessed.
	 *
	 * @param string $key
	 * @return object
	 * @throws SystemError if requested object neither existent nor creatable
	 */
	public function getLocal( $key );

	/**
	 * Create object - this will create an exclusive private instance
	 * not stored in and shared through the container.
	 *
	 * If a parent container exists and the object not creatable locally, the parent container
	 * will be asked to create the object.
	 *
	 * @param string $key
	 * @return object
	 * @throws SystemError if requested object not creatable
	 */
	public function create( $key );

	/**
	 * Create object - this will create an exclusive private instance
	 * not stored in and shared through the container.
	 *
	 * Parent containers - if any - will not be accessed.
	 *
	 * @param string $key
	 * @return object
	 * @throws SystemError if requested object not creatable
	 */
	public function createLocal( $key );

	/**
	 * Get aliases (i.e. object keys without any trailing qualifiers) of all objects that
	 * exist or can be created in the local context (i.e. exist in this container or can
	 * be created by this container's factory).
	 *
	 * @return array
	 */
	public function getAllLocalObjectAliases();

	/**
	 * Check whether the specified object exists or can be created by this container or
	 * any of it's parents (if any). In other words: Check if $key is valid.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function isKnown( $key );

	/**
	 * Check whether the specified object exists or can be created by this container only
	 * (ignores any parent containers).
	 *
	 * @param string $key
	 * @return bool
	 */
	public function isKnownLocally( $key );

	/**
	 * Check whether the specified object was already created and registered in this
	 * container or any of it's parents (if any).
	 *
	 * @param string $key
	 * @return bool
	 */
	public function exists( $key );

	/**
	 * Check whether the specified object was already created and registered in this
	 * container only (ignores any parent containers).
	 *
	 * @param string $key
	 * @return bool
	 */
	public function existsLocally( $key );
}
