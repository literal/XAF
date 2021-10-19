<?php
namespace XAF\web\exception;

use XAF\exception\NotFoundError;

/**
 * should be routed to a 404/not found response
 */
class PageNotFound extends NotFoundError
{
	public function __construct( $requestPath, $details = null )
	{
		parent::__construct('page', $requestPath, $details);
	}
}
