<?php
namespace XAF\web\infilter;

use XAF\exception\SystemError;

abstract class InputFilter
{
	/** @var array arbitrary parameters used by the derived implementations */
	private $params = [];

	/**
	 * Configure the filter
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function setParam( $name, $value )
	{
		$this->params[$name] = $value;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	protected function getParam( $name )
	{
		return isset($this->params[$name]) ? $this->params[$name] : null;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	protected function getRequiredParam( $name )
	{
		if( !isset($this->params[$name]) )
		{
			throw new SystemError('required filter parameter not set', $name);
		}
		return $this->params[$name];
	}

	abstract public function execute();
}
