<?php
namespace XAF\log\error;

interface ErrorLogger
{
	/**
	 * Write log entry
	 *
	 * @param string $errorClass
	 * @param string $message
	 * @param array $debugInfo
	 */
	public function logError( $errorClass, $message, $debugInfo = [] );
}
