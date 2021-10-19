<?php
namespace XAF\type;

use XAF\exception\SystemError;

abstract class ParamHolderBase implements ParamHolder
{
	/**
	 * Access method to be implemented by derived class
	 *
	 * @param mixed $key
	 * @return mixed shall return null if value does not exist
	 */
	abstract protected function getValue( $key );

	/**
	 * Default implementation, override as necessary
	 *
	 * @param mixed $key
	 */
	protected function throwMissingParamError( $key )
	{
		throw new SystemError('required parameter does not exist', $key);
	}

	// ************************************************************************
	// Implementation of ParamHolder
	// ************************************************************************

	public function merge( array $values )
	{
		foreach( $values as $k => $v )
		{
			$this->set($k, $v);
		}
	}

	public function get( $key, $default = null )
	{
		$value = $this->getValue($key);
		return $value !== null ? $value : $default;
	}

	public function getRequired( $key )
	{
		$value = $this->getValue($key);
		if( $value === null )
		{
			$this->throwMissingParamError($key);
		}
		return $value;
	}

	public function getInt( $key, $default = null )
	{
		$value = $this->getValue($key);
		return $value !== null ? \intval($value) : $default;
	}

	public function getRequiredInt( $key )
	{
		return \intval($this->getRequired($key));
	}

	public function getBool( $key, $default = null )
	{
		$value = $this->getValue($key);
		return $value !== null ? $this->toBool($value) : $default;
	}

	public function getRequiredBool( $key )
	{
		return $this->toBool($this->getRequired($key));
	}

	protected function toBool( $value )
	{
		return $value !== false && !\in_array(\strtolower($value), ['0', 'no', 'off', 'false'], true);
	}

	public function getArray( $key, $default = [] )
	{
		$value = $this->getValue($key);
		return $value !== null ? $this->toArray($value) : $default;
	}

	public function getRequiredArray( $key )
	{
		return $this->toArray($this->getRequired($key));
	}

	protected function toArray( $value )
	{
		return \is_array($value) ? $value : (array)$value;
	}
}
