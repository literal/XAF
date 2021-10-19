<?php
namespace XAF\http;

class ResponseHeaderSetter
{
	/** @var HeaderSender */
	private $headerSender;

	/** @var int|null */
	private $currentUnixTimestamp;

	/**
	 * @param HeaderSender $headerSender
	 */
	public function __construct( HeaderSender $headerSender )
	{
		$this->headerSender = $headerSender;
		$this->currentUnixTimestamp = \time();
	}

	/**
	 * For testing
	 *
	 * @param int $unixTimestamp
	 */
	public function setCurrentTimestamp( $unixTimestamp )
	{
		$this->currentUnixTimestamp = $unixTimestamp;
	}

	/**
	 * @param int $responseCode The HTTP status code
	 */
	public function setResponseCode( $responseCode )
	{
		$this->headerSender->setResponseCode($responseCode);
	}

	public function setNotModified()
	{
		$this->setResponseCode(304); // not modified
	}

	/**
	 * @param string $mimeType
	 * @param string|null $encoding
	 */
	public function setContentType( $mimeType, $encoding = null )
	{
		$contentType = $mimeType . ($encoding !== null ? '; charset=' . $encoding : '');
		$this->headerSender->setHeader('Content-Type', $contentType);
	}

	/**
	 * @param int $contentLength
	 */
	public function setContentLength( $contentLength )
	{
		$this->headerSender->setHeader('Content-Length', $contentLength);
	}

	/**
	 * @param string $languageTag
	 */
	public function setContentLanguage( $languageTag )
	{
		$this->headerSender->setHeader('Content-Language', $languageTag);
	}

	/**
	 * Indicate to the client that the response body shall be saved as a file
	 *
	 * @param string|null $fileName Target file name suggestion for the client
	 */
	public function setIsDownload( $fileName = null )
	{
		$this->headerSender->setHeader(
			'Content-Disposition',
			'attachment' . ($fileName != null ? '; filename="' . \utf8_decode($fileName) . '"' : '')
		);
	}

	/**
	 * @param int $cacheLifeTimeSeconds
	 * @param bool $allowPublicCaching
	 */
	public function setCacheability( $cacheLifeTimeSeconds = 0, $allowPublicCaching = false )
	{
		if( $cacheLifeTimeSeconds < 1 )
		{
			$this->sendNoCacheHeaders();
		}
		else
		{
			$this->sendExpirationHeaders($cacheLifeTimeSeconds, $allowPublicCaching);
		}
	}

	private function sendNoCacheHeaders()
	{
		$this->headerSender->setHeader('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT'); // expired already
		$this->headerSender->setHeader('Cache-Control', 'private, no-store, no-cache, must-revalidate'); // HTTP/1.1
	}

	/**
	 * @param int $cacheLifeTimeSeconds
	 * @param bool $allowPublicCaching
	 */
	private function sendExpirationHeaders( $cacheLifeTimeSeconds, $allowPublicCaching )
	{
		$this->headerSender->setHeader(
			'Expires',
			$this->unixToHttpTimestamp($this->currentUnixTimestamp + $cacheLifeTimeSeconds)
		);
		$this->headerSender->setHeader(
			'Cache-Control',
			($allowPublicCaching ? 'public' : 'private') . ', max-age=' . $cacheLifeTimeSeconds
		);
	}

	/**
	 * @param int $unixTimestamp current timestamp if null
	 * @return string
	 */
	private function unixToHttpTimestamp( $unixTimestamp )
	{
		return \gmdate('D, d M Y H:i:s', $unixTimestamp) . ' GMT';
	}

	/**
	 * @param int $unixTimestamp
	 */
	public function setLastModified( $unixTimestamp )
	{
		$this->headerSender->setHeader('Last-Modified', $this->unixToHttpTimestamp($unixTimestamp));
	}

	/**
	 * @param string $eTag
	 */
	public function setETag( $eTag )
	{
		$this->headerSender->setHeader('Etag', $eTag);
	}

	/**
	 * @param string $origin
	 */
	public function setAllowedCrossSiteXhrOrigin( $origin )
	{
		$this->headerSender->setHeader('Access-Control-Allow-Origin', $origin);
	}
}
