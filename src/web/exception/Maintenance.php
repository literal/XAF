<?php
namespace XAF\web\exception;

use XAF\exception\ExtendedError;

/**
 * Thrown when the application is temporarily not accessible because of maintenance in progress
 */
class Maintenance extends ExtendedError
{
	/**
	 * @param string $details details about what is going on
	 */
	public function __construct( $details = null )
	{
		parent::__construct('maintenance in progress', $details);
	}
}
