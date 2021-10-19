<?php
namespace XAF\exception;

use Exception;

/**
 * Class for Exceptions that provide a debug info hash and an error class string
 */
class ExtendedError extends Exception implements DebuggableError
{
	/** @var array */
	protected $debugInfo;

	public function __construct( $message, array $debugInfo = [] )
	{
		$this->debugInfo = $debugInfo;
		parent::__construct($message);
	}


	public function addDebugInfo( $key, $value )
	{
		$this->debugInfo[$key] = $value;
	}

	/**
	 * Get classification of the error - used for logging
	 *
	 * This vlue should depend on the class, not the individual exception.
	 *
	 * @return string
	 */
	public function getLogClass()
	{
		return 'exception';
	}


	/**
	 * @return array
	 */
	public function getDebugInfo()
	{
		return $this->debugInfo;
	}
}
