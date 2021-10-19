<?php
namespace XAF\web;

class ErrorHandler extends \XAF\DefaultErrorHandler
{
	/**
	 * Add HTTP request information
	 *
	 * @return array
	 */
	protected function getCommonDebugInfo()
	{
		$requestInfo = [];

		if( isset($_GET) && !empty($_GET) )
		{
			$requestInfo['get'] = $_GET;
		}

		if( isset($_POST) && !empty($_POST) )
		{
			$requestInfo['post'] = $_POST;
		}
		else
		{
			$requestBody = @\file_get_contents('php://input');
			if( $requestBody !== '' && $requestBody !== false )
			{
				$requestInfo['request body'] = $requestBody;
			}
		}

		$requestHeaders = $this->getRequestHeaders();
		if( $requestHeaders )
		{
			$requestInfo['headers'] = $requestHeaders;
		}

		return \array_replace(
			parent::getCommonDebugInfo(),
			['request' => $requestInfo]
		);
	}

	/**
	 * Liefert die vom Client geschickten HTTP-Request header
	 *
	 * @return array
	 */
	protected function getRequestHeaders()
	{
		$result = \function_exists('apache_request_headers') ? \apache_request_headers() : $_SERVER;
		foreach( ['Authorization', 'HTTP_AUTHORIZATION', 'PHP_AUTH_PW'] as $sensitiveKey )
		{
			if( isset($result[$sensitiveKey]) )
			{
				$result[$sensitiveKey] = '<censored>';
			}
		}
		return $result;
	}

	protected function displayError( $debugMessage )
	{
		$this->clearOutputBuffer();

		if( !\headers_sent() )
		{
			\header('Content-Type: text/html;charset=utf-8', true, 500); // 500 Internal Server Error
		}

		echo
			'<!DOCTYPE html>' . "\n" .
			'<html>' . "\n" .
			'<head>' . "\n" .
				'<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' . "\n" .
				'<title>Internal server error</title>' . "\n" .
			'</head>' . "\n" .
			'<body>' . "\n" .
				'<h1>Internal server error</h1>' . "\n";

		if( $this->displayDebugInfo )
		{
			echo
				'<hr>' . "\n" .
				'<pre>' . "\n" .
					\htmlspecialchars($debugMessage) . "\n" .
				'</pre>' . "\n";
		}
		echo
			'</body>' . "\n" .
			'</html>';
	}

	protected function clearOutputBuffer()
	{
		while( \ob_get_level() > 0 )
		{
			\ob_end_clean();
		}
	}
}

