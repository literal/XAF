<?php
namespace XAF\web\control;

use Exception;
use XAF\exception\DebuggableError;

/**
 * Standard controller for adding exception debug info to the response data for displaying it on
 * an error page.
 *
 * When the FrontController intercepts an exception because of a matching 'catch' directive
 * from the routing table, it will store the exception in the request var '@error'.
 *
 * This request var can then be passed to this controller by setting up an according action in the
 * internal redirection path in the routing table.
 *
 * Define in object map and use from routing table
 */
class ErrorDebugInfoProvider
{
	/**
	 * @param Exception $e
	 * @return array
	 */
	public function getDebugInfo( Exception $e )
	{
		return [
			'debugInfo' =>
				'Exception of class ' . \get_class($e) . "\n" .
				'Message: ' . $e->getMessage() . "\n" .
				(($e instanceof DebuggableError) ? 'Details: ' . \print_r($e->getDebugInfo(), true) . "\n" : '') .
				'Location: ' . $e->getFile() . ' (' . $e->getLine() . ')' . "\n" .
				'Trace: ' . "\n" . $e->getTraceAsString() . "\n" .
				// Suspended because forms might be included which lead to a *huge* dump because of
				// their reference to the ValidationService
				// 'Request vars:' . "\n" . print_r($this->requestVars, true)  . "\n\n" .
				'GET: ' . \print_r($_GET, true) . "\n\n" .
				'POST: ' . \print_r($_POST, true) . "\n\n" .
				'COOKIE: ' . \print_r($_COOKIE, true) . "\n\n" .
				'SERVER: ' . \print_r($_SERVER, true)
		];
	}
}
