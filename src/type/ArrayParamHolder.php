<?php
namespace XAF\type;

/**
 * Provide convenient access to a collection of named parameters
 */
class ArrayParamHolder extends ParamHolderBase
{
	/**
	 * @var array hashmap of all parameters
	 */
	private $params;

	public function __construct( array $params = [] )
	{
		$this->params = $params;
	}

	protected function getValue( $key )
	{
		return $this->params[$key] ?? null;
	}

	public function set( $key, $value )
	{
		$this->params[$key] = $value;
	}

	public function remove( $key )
	{
		unset($this->params[$key]);
	}
}
