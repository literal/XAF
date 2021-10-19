<?php
namespace XAF\zip;

use Exception;

class ZipArchiveMtimeSetter
{
	/** @var string */
	private $filePath;

	/** @var resource */
	private $fileHandle;

	/**
	 * @param string $zipFile
	 * @param array $mtimesByPath {<path in ZIP>: <Unix timestamp>, ...}
	 */
	public function setMtimes( $zipFile, array $mtimesByPath )
	{
		$this->filePath = $zipFile;
		$this->fileHandle = $this->open($zipFile);
		try
		{
			$this->writeMtimes($mtimesByPath);
		}
		catch( Exception $e )
		{
			$this->close();
			throw $e;
		}
		$this->close();
		$this->fileHandle = null;
		$this->filePath = null;
	}

	/**
	 * @param array $mtimesByPath {<path in ZIP>: <Unix timestamp>, ...}
	 */
	private function writeMtimes( array $mtimesByPath )
	{
		$currentBlockStartPos = 0;
		$filesWithLocalHeader = [];
		$filesInCentralDirectory = [];

		while( !$this->isEof() )
		{
			$this->seek($currentBlockStartPos);
			$blockSignature = $this->read(4);
			switch( $blockSignature )
			{
				// Local file header
				case "PK\x03\x04":
					list($filePath, $nextBlockStartPos) = $this->readLocalFileHeader($currentBlockStartPos);
					$filesWithLocalHeader[] = $filePath;
					if( isset($mtimesByPath[$filePath]) )
					{
						$this->writeMtimeToLocalFileHeader($currentBlockStartPos, $mtimesByPath[$filePath]);
					}
					$currentBlockStartPos = $nextBlockStartPos;
					break;

				// Central directory file header
				case "PK\x01\x02":
					list($filePath, $nextBlockStartPos) = $this->readCentralDirectoryFileHeader($currentBlockStartPos);
					$filesInCentralDirectory[] = $filePath;
					if( isset($mtimesByPath[$filePath]) )
					{
						$this->writeMtimeToCentralDirectoryFileHeader($currentBlockStartPos, $mtimesByPath[$filePath]);
					}
					$currentBlockStartPos = $nextBlockStartPos;
					break;

				// We are done - any other blocks (including ZIP64 stuff) come after files and central dir entries
				default:
					break 2;
			}
		}

		$filesToSetMtimeOn = \array_keys($mtimesByPath);
		$expectedFilesMissingLocalHeader = \array_diff($filesToSetMtimeOn, $filesWithLocalHeader);
		$expectedFilesMissingCentralDirectoryEntry = \array_diff($filesToSetMtimeOn, $filesInCentralDirectory);
		$missingFilesInArchive = \array_unique(
			\array_merge($expectedFilesMissingLocalHeader, $expectedFilesMissingCentralDirectoryEntry)
		);
		if( $missingFilesInArchive )
		{
			throw new ZipArchiveError('File(s) not found in archive', \implode(', ', $missingFilesInArchive));
		}
	}

	/**
	 * @var int $position
	 * @return array [<string filePath>, <int nextBlockPos>]
	 */
	private function readLocalFileHeader( $headerStartPos )
	{
		/* Offset   Length   Contents
		 *   0      4 bytes  Local file header signature (0x04034b50)
		 *   4      2 bytes  Version needed to extract
		 *   6      2 bytes  General purpose bit flag
		 *   8      2 bytes  Compression method
		 *  10      2 bytes  Last mod file time
		 *  12      2 bytes  Last mod file date
		 *  14      4 bytes  CRC-32
		 *  18      4 bytes  Compressed size (n)
		 *  22      4 bytes  Uncompressed size
		 *  26      2 bytes  Filename length (f)
		 *  28      2 bytes  Extra field length (e)
		 *	30	   (f)bytes  Filename
		 *		   (e)bytes  Extra field
		 *		   (n)bytes  Compressed data
		 */
		$this->seek($headerStartPos + 18); // Skip to compressed size
		$header = $this->read(12);
		$headerFields = \unpack('VdataLen/VuncompressedLen/vnameLen/vextraLen', $header);
		$this->seek($headerStartPos + 30); // Skip to name
		$filePath = $this->read($headerFields['nameLen']);
		$nextBlockStartPos = $headerStartPos + 30 + $headerFields['nameLen'] + $headerFields['extraLen'] + $headerFields['dataLen'];
		return [$filePath, $nextBlockStartPos];
	}

	/**
	 * @param int $headerStartPos
	 * @param int $mtimeTs
	 */
	private function writeMtimeToLocalFileHeader( $headerStartPos, $mtimeTs )
	{
		/* Offset   Length   Contents
		 *   0      4 bytes  Local file header signature (0x04034b50)
		 *  ...
		 *  10      2 bytes  Last mod file time
		 *  12      2 bytes  Last mod file date
		 *  ...
		 */
		$this->seek($headerStartPos + 10);
		$this->write(\pack('V', $this->unix2dostime($mtimeTs)));
	}

	/**
	 * @var int $position
	 * @return array [<string filePath>, <int nextBlockPos>]
	 */
	private function readCentralDirectoryFileHeader( $headerStartPos )
	{
		/* Offset   Length   Contents
		 *   0      4 bytes  Central file header signature (0x02014b50)
		 *   4      2 bytes  Version made by
		 *   6      2 bytes  Version needed to extract
		 *   8      2 bytes  General purpose bit flag
		 *  10      2 bytes  Compression method
		 *  12      2 bytes  Last mod file time
		 *  14      2 bytes  Last mod file date
		 *  16      4 bytes  CRC-32
		 *  20      4 bytes  Compressed size
		 *  24      4 bytes  Uncompressed size
		 *  28      2 bytes  Filename length (f)
		 *  30      2 bytes  Extra field length (e)
		 *  32      2 bytes  File comment length (c)
		 *  34      2 bytes  Number of disk on which this files starts
		 *  36      2 bytes  Internal file attributes
		 *  38      4 bytes  External file attributes
		 *  42      4 bytes  Relative offset of local header
		 *  46     (f)bytes  Filename
		 *         (e)bytes  Extra field
		 *         (c)bytes  File comment
		 */
		$this->seek($headerStartPos + 28); // Skip to file name length
		$header = $this->read(6);
		$headerFields = \unpack('vnameLen/vextraLen/vcommentLen', $header);
		$this->seek($headerStartPos + 46); // Skip to file name
		$filePath = $this->read($headerFields['nameLen']);
		$nextBlockStartPos = $headerStartPos + 46 + $headerFields['nameLen'] + $headerFields['extraLen'] + $headerFields['commentLen'];
		return [$filePath, $nextBlockStartPos];
	}

	/**
	 * @param int $headerStartPos
	 * @param int $mtimeTs
	 */
	private function writeMtimeToCentralDirectoryFileHeader( $headerStartPos, $mtimeTs )
	{
		/* Offset   Length   Contents
		 *   0      4 bytes  Central file header signature (0x02014b50)
		 *  ...
		 *  12      2 bytes  Last mod file time
		 *  14      2 bytes  Last mod file date
		 *  ...
		 */
		$this->seek($headerStartPos + 12);
		$this->write(\pack('V', $this->unix2dostime($mtimeTs)));
	}

	/**
	 * @param int $timestamp  Unix timestamp
	 * @return int DOS date and time
	 */
	private function unix2dostime( $timestamp )
	{
		$date = \getdate($timestamp);
		if( $date['year'] < 1980 )
		{
			return (1 << 21 | 1 << 16);
		}
		$date['year'] -= 1980;
		return (
			$date['year'] << 25 |
			$date['mon'] << 21 |
			$date['mday'] << 16 |
			$date['hours'] << 11 |
			$date['minutes'] << 5 |
			$date['seconds'] >> 1
		);
	}

	/**
	 * @param string $filePath
	 * @return resource
	 */
	private function open( $filePath )
	{
		$handle = \fopen($filePath, 'r+b');
		if( !$handle )
		{
			throw new ZipArchiveError('Failed to open file', $filePath);
		}
		return $handle;
	}

	private function seek( $position )
	{
		if( \fseek($this->fileHandle, $position) < 0 )
		{
			throw new ZipArchiveError(
				'Unexpected end of file',
				$this->filePath,
				'failed to set file pointer to ' . $position
			);
		}
	}

	private function read( $length )
	{
		$result = \fread($this->fileHandle, $length);
		if( $result === false )
		{
			throw new ZipArchiveError('Failed to read from file', $this->filePath);
		}
		if( \strlen($result) != $length )
		{
			throw new ZipArchiveError('Unexpected end of file', $this->filePath);
		}
		return $result;
	}

	private function write( $data )
	{
		if( \fwrite($this->fileHandle, $data) !== \strlen($data) )
		{
			throw new ZipArchiveError('Failed to write to file', $this->filePath);
		}
	}

	private function isEof()
	{
		return \feof($this->fileHandle);
	}

	private function close()
	{
		if( $this->fileHandle )
		{
			if( !\fclose($this->fileHandle) )
			{
				throw new ZipArchiveError('Failed to close file', $this->filePath);
			}
		}
	}
}
