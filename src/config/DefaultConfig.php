<?php
namespace XAF\config;

use XAF\exception\SystemError;

use XAF\type\ParamHolder,
	XAF\type\ParamHolderBase;

/**
 * Provides configuration values
 *
 * Keys are hierarchical dot-separated, e.g. 'main.sub.key' - or null for root
 *
 * Configuration can be hierarchically set from different sources (i.e. master config, overridden
 * by app config, overridden by app instance config)
 */
class DefaultConfig extends ParamHolderBase implements Config
{
	/** @var array */
	private $data;

	public function __construct( $data = [] )
	{
		$this->data = $data;
	}

	/**
	 * Import - if key is already defined, anything at/below it will be overwritten
	 *
	 * @param string|null $key dot-separated, e.g. 'main.sub.key' - or null for root
	 * @param mixed $data
	 */
	public function import( $key, $data )
	{
		$this->set($key, $data);
	}

	public function set( $key, $value )
	{
		$targetRef =& $this->getOrCreateNodeReference($key);
		$targetRef = $value;
	}

	public function remove( $key )
	{
		$targetRef =& $this->getOrCreateNodeReference($key);
		$targetRef = null;
	}

	/**
	 * "deep-merge" with existing options if key is already defined
	 *
	 * Only leaf nodes that are present in both the existing and the imported data will
	 * be overwritten
	 *
	 * @param string|null $key dot-separated, e.g. 'main.sub.key' - or null for root
	 * @param mixed $data
	 */
	public function mergeBranch( $key, $data )
	{
		$targetRef =& $this->getOrCreateNodeReference($key);
		if( \is_array($targetRef) && \is_array($data) )
		{
			$targetRef = \array_replace_recursive($targetRef, $data);
		}
		else
		{
			$targetRef = $data;
		}
	}

	/**
	 * Return a reference to the node of $this->data that corresponds to the key
	 *
	 * If that node is not yet defined, it will be created with an initial value of null.
	 *
	 * If any of the nodes along the way from the data root to the specified key is
	 * not an array it will be destroyed and replaced by an array!
	 *
	 * @param string|null $key
	 * @return mixed
	 */
	private function &getOrCreateNodeReference( $key )
	{
		if( $key === null )
		{
			return $this->data;
		}

		$keyParts = \explode('.', $key);
		$target =& $this->data;
		foreach( $keyParts as $keyPart )
		{
			if( !\is_array($target) )
			{
				$target = [];
			}
			if( !\array_key_exists($keyPart, $target) )
			{
				$target[$keyPart] = null;
			}
			$target =& $target[$keyPart];
		}

		return $target;
	}

	/**
	 * @param string|null $key
	 * @return mixed null if key does not exist
	 */
	protected function getValue( $key )
	{
		if( $key === null )
		{
			return $this->data;
		}

		$keyParts = \explode('.', $key);
		$data = $this->data;
		foreach( $keyParts as $keyPart )
		{
			if( !\is_array($data) || !\array_key_exists($keyPart, $data) )
			{
				return null;
			}

			$data = $data[$keyPart];
		}
		return $data;
	}

	protected function throwMissingParamError( $key )
	{
		throw new SystemError('required config option not defined', $key);
	}

	/**
	 * @param mixed $key
	 * @return ParamHolder
	 * @throws SystemError if key not found
	 */
	public function export( $key )
	{
		$value = $this->getRequired($key);
		if( !\is_array($value) )
		{
			throw new SystemError('config sub-tree to be exported not an array', $key);
		}
		return new DefaultConfig($value);
	}
}
