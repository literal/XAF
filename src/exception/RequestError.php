<?php
namespace XAF\exception;

/**
 * Thrown when the application cannot fulfil a request
 */
class RequestError extends ValueRelatedError
{
	/**
	 * @return string
	 */
	public function getLogClass()
	{
		return 'request error';
	}
}
