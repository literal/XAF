<?php
namespace XAF\file;

class ProgressiveFileReader
{
	/** @var string */
	private $filePath;

	/** @var resource */
	private $fileHandle;

	/** @var bool */
	private $isEndOfFile = false;

	/**
	 * @param string $sourceFilePath
	 */
	public function __construct( $sourceFilePath )
	{
		$this->filePath = $sourceFilePath;
	}

	/**
	 * @return int
	 */
	public function getSize()
	{
		$result = @\filesize($this->filePath);
		if( $result === false )
		{
			throw new FileError('could not get file size of', $this->filePath);
		}
		return $result;
	}

	/**
	 * @param int $maxBytesToReturn
	 * @return string
	 */
	public function read( $maxBytesToReturn )
	{
		if( $this->isEndOfFile() )
		{
			return '';
		}

		$this->openFileIfClosed();
		$result = $this->readFromFile($maxBytesToReturn);
		$this->closeFileIfFinishedReading();
		return $result;
	}

	private function openFileIfClosed()
	{
		if( $this->fileHandle )
		{
			return;
		}

		$this->fileHandle = @\fopen($this->filePath, 'rb');
		if( !$this->fileHandle )
		{
			throw new FileError('failed to open file', $this->filePath);
		}
	}

	/**
	 * @param int $maxBytesToReturn
	 * @return string
	 */
	private function readFromFile( $maxBytesToReturn )
	{
		if( !$this->fileHandle )
		{
			return '';
		}

		$result = @\fread($this->fileHandle, $maxBytesToReturn);
		if( $result === false )
		{
			throw new FileError('failed to read from file', $this->filePath);
		}
		return $result;
	}

	private function closeFileIfFinishedReading()
	{
		if( $this->fileHandle && \feof($this->fileHandle) )
		{
			\fclose($this->fileHandle);
			$this->fileHandle = null;
			$this->isEndOfFile = true;
		}
	}

	/**
	 * @return bool
	 */
	public function isEndOfFile()
	{
		return $this->isEndOfFile;
	}
}
