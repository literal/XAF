<?php
namespace XAF\type;

use XAF\exception\SystemError;

/**
 * Provide convenient read access to a collection of named parameters
 */
interface ParamHolder
{
	/**
	 * @param mixed $key
	 * @param mixed $default value returned if requested parameter does not exist
	 * @return mixed
	 */
	public function get( $key, $default = null );

	/**
	 * @param mixed $key
	 * @return mixed
	 * @throws SystemError if requested parameter does not exist
	 */
	public function getRequired( $key );


	/**
	 * @param string $key
	 * @param mixed $default value returned if requested param does not exist
	 * @return int|null
	 */
	public function getInt( $key, $default = null );

	/**
	 * @param mixed $key
	 * @return int
	 * @throws SystemError if requested parameter does not exist
	 */
	public function getRequiredInt( $key );

	/**
	 * @param string $key
	 * @param mixed $default value returned if requested parameter does not exist
	 * @return bool|null
	 */
	public function getBool( $key, $default = null );

	/**
	 * @param mixed $key
	 * @return bool
	 * @throws SystemError if requested parameter does not exist
	 */
	public function getRequiredBool( $key );

	/**
	 * @param string $key
	 * @param array $default value returned if requested parameter does not exist
	 * @return array
	 */
	public function getArray( $key, $default = [] );

	/**
	 * @param string $key
	 * @return array
	 * @throws SystemError if requested parameter does not exist
	 */
	public function getRequiredArray( $key );

	/**
	 * @param mixed $key
	 * @param mixed $value
	 */
	public function set( $key, $value );

	/**
	 * @param mixed $key
	 */
	public function remove( $key );

	/**
	 * @param array $values
	 */
	public function merge( array $values );
}
