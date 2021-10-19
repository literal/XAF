<?php
namespace XAF;

interface ErrorHandler
{
	/**
	 * @param \Throwable $e No type hint to maintain PHP 5.6 compatibility
	 */
	public function handleException( $e );

	/**
	 * @param \Throwable $e No type hint to maintain PHP 5.6 compatibility
	 */
	public function logException( $e );

	/**
	 * @param string $errorClass
	 * @param string $message
	 * @param array $debugInfo
	 */
	public function logError( $errorClass, $message, $debugInfo = [] );
}
