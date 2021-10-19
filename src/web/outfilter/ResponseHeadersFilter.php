<?php
namespace XAF\web\outfilter;

use XAF\http\ResponseHeaderSetter;
use XAF\web\Response;

/**
 * Generate HTTP response headers
 */
class ResponseHeadersFilter extends OutputFilter
{
	/** @var ResponseHeaderSetter */
	private $responseHeaderSetter;

	/** @var int */
	private $httpStatus = 200;

	/** @var string */
	private $mimeType = 'text/html';

	/** @var string|null */
	private $encoding;

	/** @var string|null */
	private $language;

	/** @var bool */
	private $sendForDownload = false;

	/** @var string|null */
	private $downloadFileName;

	/** @var int|null */
	private $cacheLifeTimeSeconds;

	/** @var bool */
	private $allowPublicCaching = true;

	/** @var string|null */
	private $allowedCrossSiteXhrOrigin;

	public function __construct( ResponseHeaderSetter $responseHeaderSetter )
	{
		$this->responseHeaderSetter = $responseHeaderSetter;
	}

	/**
	 * @param int $status
	 */
	public function setHttpStatus( $status )
	{
		$this->httpStatus = $status;
	}

	/**
	 * @param string $mimeType e.g. 'text/html'
	 * @param string|null $encoding e.g. 'utf-8'
	 */
	public function setContentType( $mimeType, $encoding = null )
	{
		$this->mimeType = $mimeType;
		$this->encoding = $encoding;
	}

	/**
	 * @param string|null $languageTag
	 */
	public function setContentLanguage( $languageTag )
	{
		$this->language = $languageTag;
	}

	/**
	 * @param boolean $sendForDownload
	 */
	public function setSendForDownload( $sendForDownload )
	{
		$this->sendForDownload = $sendForDownload;
	}

	/**
	 * @param string|null $fileName
	 */
	public function setDownloadFileName( $fileName )
	{
		$this->downloadFileName = $fileName;
	}

	/**
	 * @param int $lifeTimeSeconds 0 for no cache
	 */
	public function setCacheLifetimeSeconds( $lifeTimeSeconds )
	{
		$this->cacheLifeTimeSeconds = $lifeTimeSeconds;
	}

	/**
	 * @param bool $allowPublicCaching Whether to allow caching of the response in public proxy server
	 *     (only has an effect if caching is allowed at all, i.e. a life time is set)
	 */
	public function setAllowPublicCaching( $allowPublicCaching )
	{
		$this->allowPublicCaching = $allowPublicCaching;
	}

	/**
	 * Required to signal the browser that an XHR request from script in the context of another domain
	 * may be executed (or more precisely: that this server's response may be passed to that script)
	 * @see http://www.w3.org/TR/cors/
	 *
	 * @param string|null $origin Either <protocol>://<domain> or '*' for any domain
	 */
	public function setAllowedCrossSiteXhrOrigin( $origin )
	{
		$this->allowedCrossSiteXhrOrigin = $origin;
	}

	public function execute( Response $response )
	{
		$this->responseHeaderSetter->setResponseCode($this->httpStatus);
		$this->responseHeaderSetter->setContentType($this->mimeType, $this->encoding);

		if( isset($this->language) )
		{
			$this->responseHeaderSetter->setContentLanguage($this->language);
		}

		if( $this->sendForDownload )
		{
			$this->responseHeaderSetter->setIsDownload($this->downloadFileName);
		}

		$this->responseHeaderSetter->setCacheability($this->cacheLifeTimeSeconds, $this->allowPublicCaching);

		if( $this->allowedCrossSiteXhrOrigin !== null )
		{
			$this->responseHeaderSetter->setAllowedCrossSiteXhrOrigin($this->allowedCrossSiteXhrOrigin);
		}
	}
}
