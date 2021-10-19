<?php
namespace XAF\web\control;

use XAF\ErrorHandler;
use Exception;

/**
 * Standard controller for logging exceptions during an internal redirect pass.
 * When the FrontController intercepts an exception because of a matching 'catch' directive
 * from the routing table, it will store the exception in the request var '@error'.
 *
 * This request var can then be passed to this controller by setting up an according action in the
 * internal redirection path in the routing table.
 *
 * Define in object map and use from routing table
 */
class ErrorLoggingController
{
	/** @var ErrorHandler  */
	protected $errorHandler;

	public function __construct( ErrorHandler $errorHandler )
	{
		$this->errorHandler = $errorHandler;
	}

	public function logError( Exception $e = null )
	{
		if( $e )
		{
			$this->errorHandler->logException($e, true);
		}
	}

	public function logErrorShort( Exception $e = null )
	{
		if( $e )
		{
			$this->errorHandler->logException($e, false);
		}
	}
}
