<?php
namespace XAF\http;

use XAF\exception\SystemError;

/**
 * Serve local file via HTTP observing HTTP cache headers and send a HTTP 304 "not modified" response
 * if the client has the current version.
 */
class FileSender
{
	/** @var ResponseHeaderSetter */
	private $responseHeaderSetter;

	/** @var int */
	protected $httpCacheLifetimeSeconds = 0;

	/** @var bool */
	protected $allowPublicCaching = false;

	public function __construct( ResponseHeaderSetter $responseHeaderSetter )
	{
		$this->responseHeaderSetter = $responseHeaderSetter;
	}

	/**
	 * @param int $lifetimeSeconds Number of seconds after which a HTTP client shall check for a new version,
	 *     0 for no cache
	 */
	public function setHttpCacheLifetimeSeconds( $lifetimeSeconds )
	{
		$this->httpCacheLifetimeSeconds = $lifetimeSeconds;
	}

	/**
	 * @param bool $allowPublicCaching Whether to allow caching of the response in public proxy server
	 *     (only has an effect if caching is allowed at all, i. e. a life time is set)
	 */
	public function setAllowPublicCaching( $allowPublicCaching )
	{
		$this->allowPublicCaching = $allowPublicCaching;
	}

	/**
	 * @param string $localFile
	 * @param string $mimeType
	 */
	public function sendFile( $localFile, $mimeType )
	{
		$modifiedTs = \filemtime($localFile);
		$eTag = \md5($localFile . '@' . $modifiedTs);

		if( $this->doesClientHaveCurrentVersion($eTag, $modifiedTs) )
		{
			$this->responseHeaderSetter->setNotModified();
		}
		else
		{
			$this->responseHeaderSetter->setContentType($mimeType);
			$this->responseHeaderSetter->setContentLength(\filesize($localFile));
			$this->responseHeaderSetter->setCacheability($this->httpCacheLifetimeSeconds, $this->allowPublicCaching);
			$this->responseHeaderSetter->setLastModified($modifiedTs);
			$this->responseHeaderSetter->setETag($eTag);
			if( false === @\readfile($localFile) )
			{
				throw new SystemError('failed to read file', $localFile);
			}
		}
	}

	/**
	 * @param string $currentEtag
	 * @param int $currentTimestamp
	 * @return bool
	 */
	protected function doesClientHaveCurrentVersion( $currentEtag, $currentTimestamp )
	{
		if( isset($_SERVER['HTTP_IF_NONE_MATCH']) )
		{
			if( $_SERVER['HTTP_IF_NONE_MATCH'] == $currentEtag )
			{
				return true;
			}
		}
		else if( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) )
		{
			$modifiedSinceTs = @\strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
			if( $modifiedSinceTs && $modifiedSinceTs >= $currentTimestamp )
			{
				return true;
			}
		}
		return false;
	}
}

