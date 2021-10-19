<?php
namespace XAF\http;

// @todo Too much in common with FileSender, factor out to helper, base class or whatever
// Cannot be tested because the output buffers are cleared and flush() is called
class DownloadSender
{
	/** @var ResponseHeaderSetter */
	private $responseHeaderSetter;

	/** @var int */
	protected $httpCacheLifetimeSeconds = 0;

	/** @var bool */
	protected $allowPublicCaching = false;

	/** @var int */
	private $transmitBlockSize = 131072; // 128 KiB

	/** @var int */
	private $maxDurationSec = 0; // Unlimited

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
	 *     (only has an effect if caching is allowed at all, i.e. a life time is set)
	 */
	public function setAllowPublicCaching( $allowPublicCaching )
	{
		$this->allowPublicCaching = $allowPublicCaching;
	}

	/**
	 * @param int $transmitBlockSize
	 */
	public function setTransmitBlockSize( $transmitBlockSize )
	{
		$this->transmitBlockSize = $transmitBlockSize;
	}

	/**
	 * @param int
	 */
	public function setMaxDurationSec( $maxDurationSec )
	{
		$this->maxDurationSec = $maxDurationSec;
	}

	/**
	 * @param ChunkedDataSource $dataSource
	 * @return array {complete: <bool>, bytesSent: <int>, durationMs: <int>}
	 */
	public function deliverDownload( ChunkedDataSource $dataSource )
	{
		$this->clearOutputBuffers();

		$contentLength = $dataSource->getLength();

		$this->responseHeaderSetter->setContentType($dataSource->getMimeType());
		$this->responseHeaderSetter->setContentLength($contentLength);
		$this->responseHeaderSetter->setCacheability($this->httpCacheLifetimeSeconds, $this->allowPublicCaching);
		$this->responseHeaderSetter->setIsDownload($dataSource->getFileName());

		\ignore_user_abort(true);
		\set_time_limit($this->maxDurationSec);

		$bytesSent = 0;
		$t0 = \round(\microtime(true) * 1000);
		while( !$dataSource->isEndReached() )
		{
			if( \connection_aborted() )
			{
				break;
			}
			$chunk = $dataSource->getChunk($this->transmitBlockSize);
			echo $chunk;
			@\flush();
			$bytesSent += \strlen($chunk);
		}
		$durationMs = \round(\microtime(true) * 1000) - $t0;

		return [
			'complete' => $bytesSent >= $contentLength,
			'bytesSent' => $bytesSent,
			'durationMs' => $durationMs
		];
	}

	protected function clearOutputBuffers()
	{
		while( \ob_get_level() > 0 )
		{
			\ob_end_clean();
		}
	}
}
