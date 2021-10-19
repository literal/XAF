<?php
namespace XAF\http;

use XAF\file\ProgressiveFileReader;

class ChunkedFileDataSource extends ChunkedDataSource
{
	/** @var ProgressiveFileReader */
	private $fileReader;

	/**
	 * @param ProgressiveFileReader $fileReader
	 * @param string $mimeType
	 * @param string|null $fileName
	 */
	public function __construct( ProgressiveFileReader $fileReader, $mimeType, $fileName = null )
	{
		$this->fileReader = $fileReader;
		parent::__construct($mimeType, $fileName);
	}

	/**
	 * @return int
	 */
	public function getLength()
	{
		return $this->fileReader->getSize();
	}

	/**
	 * @param int $maxSize
	 * @return string
	 */
	public function getChunk( $maxSize )
	{
		return $this->fileReader->read($maxSize);
	}

	/**
	 * @return bool
	 */
	public function isEndReached()
	{
		return $this->fileReader->isEndOfFile();
	}
}

