<?php
namespace XAF\http;

class HeaderSender
{
	/**
	 * @param int $code The HTTP status code
	 */
	public function setResponseCode( $code )
	{
		\http_response_code($code);
	}

	/**
	 * Set a header replacing any existing header(s) of the same name
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function setHeader( $name, $value )
	{
		\header($name . ': ' . $value, true);
	}

	/**
	 * Add a header adding keeping any existing header(s) of the same name
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function appendHeader( $name, $value )
	{
		\header($name . ': ' . $value, false);
	}
}
