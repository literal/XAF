<?php
namespace XAF\http;

use XAF\zip\ProgressiveZipBuilder;

/**
 * Wrapper for ProgressiveZipBuilder for fetching data in arbitrary length chunks
 */
class ChunkedZipDataSource extends ChunkedDataSource
{
	/** @var ProgressiveZipBuilder */
	private $zipBuilder;

	/** @var string */
	private $buffer = '';

	/**
	 * @param ProgressiveZipBuilder $zipBuilder
	 * @param string|null $fileName
	 */
	public function __construct( ProgressiveZipBuilder $zipBuilder, $fileName = null )
	{
		$this->zipBuilder = $zipBuilder;
		parent::__construct('application/zip', $fileName);
	}

	public function getLength()
	{
		return $this->zipBuilder->predictArchiveLength();
	}

	public function getChunk( $maxSize )
	{
		$result = '';
		$bytesLeftToReturn = $maxSize;

		while( $bytesLeftToReturn > 0 && !$this->isEndReached() )
		{
			$resultChunk = $this->fetchData($bytesLeftToReturn);
			$resultChunkLength = \strlen($resultChunk);
			$result .= $resultChunk;
			$bytesLeftToReturn -= $resultChunkLength;
		}

		return $result;
	}

	private function fetchData( $maxBytes )
	{
		if( $this->buffer === '' )
		{
			$this->buffer = $this->zipBuilder->getNextChunk();
		}

		$bytesInBuffer = \strlen($this->buffer);
		if( $bytesInBuffer > $maxBytes )
		{
			$result = \substr($this->buffer, 0, $maxBytes);
			$this->buffer = \substr($this->buffer, $maxBytes);
		}
		else
		{
			$result = $this->buffer;
			$this->buffer = '';
		}
		return $result;
	}

	public function isEndReached()
	{
		return $this->buffer === '' && !$this->zipBuilder->hasMoreChunks();
	}
}
