<?php
namespace XAF\http;

/**
 * Send HTTP error response along with a plaintext or HTML error message
 */
class ErrorMessageSender
{
	/** @var ResponseHeaderSetter */
	private $responseHeaderSetter;

	public function __construct( ResponseHeaderSetter $responseHeaderSetter )
	{
		$this->responseHeaderSetter = $responseHeaderSetter;
	}

	public function sendPlaintextError( $httpResponseCode, $message )
	{
		$this->clearOutputBuffer();
		if( !\headers_sent() )
		{
			$this->responseHeaderSetter->setResponseCode($httpResponseCode);
			$this->responseHeaderSetter->setContentType('text/plain', 'utf-8');
		}
		echo $message;
	}

	private function clearOutputBuffer()
	{
		while( \ob_get_level() > 0 )
		{
			\ob_end_clean();
		}
	}
}

