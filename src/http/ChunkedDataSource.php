<?php
namespace XAF\http;

/**
 * Simple read-only stream source taylored to serve as source for sending HTTP streams/downloads
 */
abstract class ChunkedDataSource
{
	/** @var string */
	protected $mimeType;

	/** @var string|null */
	protected $fileName;

	/**
	 * @param string $mimeType
	 * @param string $fileName Recommended file name for the recipient of the data
	 */
	public function __construct( $mimeType, $fileName = null )
	{
		$this->mimeType = $mimeType;
		$this->fileName = $fileName;
	}

	public function getMimeType()
	{
		return $this->mimeType;
	}

	public function getFileName()
	{
		return $this->fileName;
	}

	/**
	 * @return int
	 */
	abstract public function getLength();

	/**
	 * @param int $maxSize
	 * @return string
	 */
	abstract public function getChunk( $maxSize );

	/**
	 * @return bool
	 */
	abstract public function isEndReached();
}
