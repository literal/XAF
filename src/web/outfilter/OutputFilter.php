<?php
namespace XAF\web\outfilter;

use XAF\web\Response;

abstract class OutputFilter
{
	/** @var array arbitrary parameters used by the derived implementations */
	protected $params = [];

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
	 * @param mixed $response
	 */
	abstract public function execute( Response $response );
}
