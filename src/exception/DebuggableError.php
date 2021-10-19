<?php
namespace XAF\exception;

/**
 * For exceptions that carry extra debug information for logging (in addition to the normal backtrace)
 */
interface DebuggableError
{
	/**
	 * Get classification of the error - used for logging
	 *
	 * This value should depend on the class, not the individual exception.
	 *
	 * @return string
	 */
	public function getLogClass();

	/**
	 * Get a hash of debug info fields to be logged
	 *
	 * @return array
	 */
	public function getDebugInfo();
}
