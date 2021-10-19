<?php
namespace XAF\zip;

use XAF\file\FileHelper;

/**
 * Create uncompressed ZIP archive without storing it in a disk file (result can be fetched as string chunks instead)
 *
 * Intended for on-the-fly zipping and streaming of multiple media files (which by nature are not compressible anyway).
 *
 * ZIP64 is partly implemented so that the archive size can exceed 4GiB (but individual file size may not).
 *
 * Zip format version is 4.5 because that's where ZIP64 was first introduced.
 *
 * Zip File Structure:
 *  + n * (<local file header> + <file contents>)
 *  + <central directory>
 *  + <ZIP64 end of directoty record>
 *  + <ZIP64 end of directoty locator>
 *  + <end of directoty record (central directory locator)>
 *
 * The central directory duplicates the file metadata which also precedes each file contents chunk
 *
 * @todo Implement further ZIP64 extensions so the total number of files can go beyond 64 k and individual files
 *    may be larger than 4 GiB
 */
class ProgressiveZipBuilder
{
	const VERSION_MADE_BY = 0x003F; // High byte: OS (0 = MS DOS), low byte: PKZIP version * 10 (0x3F = V 6.3)
	const VERSION_NEEDED_TO_EXTRACT_PLAIN = 0x0014; // High byte: OS (0 = MS DOS), low byte: PKZIP version * 10 (0x14 = V 2.0)
	const VERSION_NEEDED_TO_EXTRACT_ZIP64 = 0x002D; // High byte: OS (0 = MS DOS), low byte: PKZIP version * 10 (0x2D = V 4.5)

	/** @var FileHelper */
	private $fileHelper;

	/** @var int Unix timestamp of object instance creation - default for file "last modified" timestamps */
	private $currentTimestamp;

	/** @var array List of hashes containing informatioin on the files added so far */
	private $fileRecords = [];

	/** @var int Byte offset into the archive of the next file that will be added */
	private $nextFileToAddOffset = 0;

	/** @var int Index of the next file in $files to return from the next call of getNextChunk() */
	private $nextFileToDeliverIndex = 0;

	/** @var bool */
	private $haveTrailingRecordsBeenDelivered = false;

	/** @var bool */
	private $shallForceZip64Format = false;

	public function __construct( FileHelper $fileHelper )
	{
		$this->fileHelper = $fileHelper;
		$this->currentTimestamp = \time();
	}

	/**
	 * Whether to use ZIP64 format even for archives below 4 GiB
	 *
	 * @param bool $forceZip64Format
	 */
	public function setForceZip64Format( $forceZip64Format )
	{
		$this->shallForceZip64Format = $forceZip64Format;
	}

	/**
	 * @param string $sourcePath
	 * @param string $nameInArchive
	 * @param int|null $timestamp Last modified unix timestamp to store in archive
	 */
	public function addFile( $sourcePath, $nameInArchive = null, $timestamp = null )
	{
		$nameInArchive = $nameInArchive ?: \basename($sourcePath);
		$fileSize = $this->fileHelper->getFileSize($sourcePath);

		$fileRecord = [
			'offset' => $this->nextFileToAddOffset,
			'source' => $sourcePath,
			'name' => $nameInArchive,
			'size' => $fileSize,
			'timestamp' => $timestamp ?: $this->currentTimestamp,
			// ATTENTION: for performance reasons the real crc is only computed when the file is actually delivered
			'crc' => 0
		];
		$this->fileRecords[] = $fileRecord;

		$this->nextFileToAddOffset += \strlen($this->buildLocalFileHeader($fileRecord)) + $fileSize;
	}

	/**
	 * @return int total archive length in bytes
	 */
	public function predictArchiveLength()
	{
		return $this->nextFileToAddOffset + \strlen($this->buildTrailingRecords());
	}

	/**
	 * @return bool
	 */
	public function hasMoreChunks()
	{
		return $this->nextFileToDeliverIndex < \sizeof($this->fileRecords) || !$this->haveTrailingRecordsBeenDelivered;
	}

	/**
	 * @return string
	 */
	public function getNextChunk()
	{
		$result = '';

		if( $this->nextFileToDeliverIndex < \sizeof($this->fileRecords) )
		{
			$result = $this->buildFileChunk($this->nextFileToDeliverIndex);
			$this->nextFileToDeliverIndex++;
		}
		else if( !$this->haveTrailingRecordsBeenDelivered )
		{
			$result = $this->buildTrailingRecords();
			$this->haveTrailingRecordsBeenDelivered = true;
		}

		return $result;
	}

	/**
	 * ATTENTION, SIDE EFFECT: Also populates the file's 'crc' field for performance reasons!
	 *
	 * @param $fileIndex
	 * @return string
	 */
	private function buildFileChunk( $fileIndex )
	{
		$fileContents = $this->fileHelper->getFileContents($this->fileRecords[$fileIndex]['source']);
		$this->fileRecords[$fileIndex]['crc'] = \crc32($fileContents);
		return $this->buildLocalFileHeader($this->fileRecords[$fileIndex]) . $fileContents;
	}

	/**
	 * @return string
	 */
	private function buildTrailingRecords()
	{
		$result = $this->buildCentralDirectory();
		$centralDirectorySize = \strlen($result);

		if( $this->shallUseZip64Format() )
		{
			$result .= $this->buildZip64EndOfCentralDirectoryRecord($centralDirectorySize);
			$result .= $this->buildZip64EndOfCentralDirectoryLocator($centralDirectorySize);
		}

		$result .= $this->buildEndOfCentralDirectoryRecord($centralDirectorySize);

		return $result;
	}

	/**
	 * Local file header:
	 * Offset   Length   Contents
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
	 *		   (f)bytes  Filename
	 *		   (e)bytes  Extra field
	 *		   (n)bytes  Compressed data
	 *
	 * @param array $fileRecord
	 * @return string
	 */
	private function buildLocalFileHeader( $fileRecord )
	{
		return \pack(
				'VvvvVVVVvv',
				0x04034b50, // Local file header signature
				$this->getVersionNeededToExtract(),
				0x0800, // General purpose bit flag: bit 11 set = file name is UTF-8 encoded (not yet supported by all clients)
				0x0000, // Compression method: 0 = stored
				$this->unix2dostime($fileRecord['timestamp']),
				$fileRecord['crc'],
				$fileRecord['size'], // Compressed size (same as uncompressed, because we use the store method)
				$fileRecord['size'], // Uncompressed size
				\strlen($fileRecord['name']),
				0
			)
			. $fileRecord['name'];
	}

	/**
	 * @return string
	 */
	private function buildCentralDirectory()
	{
		$result = '';
		foreach( $this->fileRecords as $fileRecord )
		{
			$result .= $this->buildCentralDirectoryEntry($fileRecord);
		}
		return $result;
	}

	/**
	 * Central directory entry:
	 *
	 * Offset   Length   Contents
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
	 *
	 * @param array $fileRecord
	 * @return string
	 */
	private function buildCentralDirectoryEntry( array $fileRecord )
	{
		$addZip64ExtraBlock = $fileRecord['offset'] >= 0xFFFFFFFF;

		$result = \pack(
				'VvvvvVVVVvvvvvVV',
				0x02014b50, // Central file header signature
				self::VERSION_MADE_BY,
				$this->getVersionNeededToExtract(),
				0x0800, // General purpose bit flag: bit 11 set = file name is UTF-8 encoded (not yet supported by all clients)
				0x0000, // Compression method: 0 = stored
				$this->unix2dostime($fileRecord['timestamp']),
				$fileRecord['crc'],
				$fileRecord['size'], // Compressed file size (same as uncompressed, because we use the store method)
				$fileRecord['size'], // Uncompressed file size
				\strlen($fileRecord['name']),
				$addZip64ExtraBlock ? 12 : 0, // Extra field length
				0, // File comment length
				0, // Number of disk on which this files starts
				0, // Internal file attributes
				// External file attributes: OS dependent (OS type depends on version made by), for MSDOS,
				// the low byte contains FAT file attribute flags, 0x20 = archive flag set
				0x0020,
				// Offset of the file's local header, 0xFFFFFFFF means "see ZIP64 extra block"
				$addZip64ExtraBlock ? 0xFFFFFFFF : $fileRecord['offset']
			)
			. $fileRecord['name'];

		if( $addZip64ExtraBlock )
		{
			$result .= \pack(
					'vv',
					1,  // Marker for ZIP64 extra block (2 bytes)
					8   // Size of extra block data (2 bytes) - ATTENTION: ZIP spec calls this field the size of the
						// extra block but trial and error yields that this is the size of the net data excluding extra
						// block type and this length field (otherwise some programms fail to extract high files
						// beyond 4 GiB properly)
				)
				// Offset of the local file header from the start of the ZIP file (8 bytes)
				. $this->pack64bitUnsignedLE($fileRecord['offset']);
		}
		return $result;

	}

	/**
	 * ZIP64 end of central directory record (56 bytes):
	 *
	 * Offset   Length   Contents
	 *   0      4 bytes  ZIP64 end of central dir signature (0x06064b50)
	 *   4      8 bytes  Size of ZIP64 end of central directory record (counted from next field, i.e. total - 12)
	 *  12      2 bytes  Version made by
	 *  14      2 bytes  Version needed to extract
	 *  16      4 bytes  Number of this disk
	 *  20      4 bytes  Number of the disk with the start of the central directory
	 *  24      8 bytes  Total number of entries in the central directory on this disk
	 *  32      8 bytes  Total number of entries in the central directory
	 *  40      8 bytes  Size of the central directory
	 *  48      8 bytes  Offset of start of central directory with respect to the starting disk number
	 *  56      variable ZIP64 extensible data sector (not used)
	 *
	 * @param int $centralDirectorySize
	 * @return string
	 */
	private function buildZip64EndOfCentralDirectoryRecord( $centralDirectorySize )
	{
		$totalFileCount = \sizeof($this->fileRecords);
		return \pack('V', 0x06064b50)
			. $this->pack64bitUnsignedLE(44) // Size of this record without signature and this size field
			. \pack(
				'vvVV',
				self::VERSION_MADE_BY,
				self::VERSION_NEEDED_TO_EXTRACT_ZIP64,
				0, // Number of this disk
				0  // Number of disk with central directory
			)
			. $this->pack64bitUnsignedLE($totalFileCount) // Total number of central directory entries (this disk)
			. $this->pack64bitUnsignedLE($totalFileCount) // Total number of central directory entries (global)
			. $this->pack64bitUnsignedLE($centralDirectorySize) // Size of central directory
			. $this->pack64bitUnsignedLE($this->nextFileToAddOffset); // Offset of start of central dir
	}

	/**
	 * ZIP64 end of central directory locator (20 bytes):
	 *
	 * Offset   Length   Contents
	 *   0      4 bytes  ZIP64 end of central dir locator signature (0x07064b50)
	 *   4      4 bytes  Number of the disk with the start of the ZIP64 end of central directory
     *   8      8 bytes  Relative offset of the zip64 end of central directory record
     *  16      4 bytes  Total number of disks
	 *
	 * @param int $centralDirectorySize
	 * @return string
	 */
	private function buildZip64EndOfCentralDirectoryLocator( $centralDirectorySize )
	{
		return \pack(
				'VV',
				0x07064B50,
				0  // Number of the disk with the start of the ZIP64 end of central directory
			)
			// Offset of the zip64 end of central directory record
			. $this->pack64bitUnsignedLE($this->nextFileToAddOffset + $centralDirectorySize)
			. \pack('V', 1); // Total number of disks
	}

	/**
	 * End of central directory record (22 bytes):
	 *
	 * Offset   Length   Contents
	 *   0      4 bytes  End of central dir signature (0x06054b50)
	 *   4      2 bytes  Number of this disk
	 *   6      2 bytes  Number of the disk with the start of the central directory
	 *   8      2 bytes  Total number of entries in the central dir on this disk
	 *  10      2 bytes  Total number of entries in the central dir
	 *  12      4 bytes  Size of the central directory
	 *  16      4 bytes  Offset of start of central directory with respect to the starting disk number
	 *  20      2 bytes  Zipfile comment length (c)
	 *  22     (c)bytes  Zipfile comment
	 *
	 * @param int $centralDirectorySize
	 * @return string
	 */
	private function buildEndOfCentralDirectoryRecord( $centralDirectorySize )
	{
		$totalFileCount = \sizeof($this->fileRecords);
		return \pack(
			'VvvvvVVv',
			0x06054B50,
			0, // Number of this disk
			0, // Number of the disk with the start of the central directory
			// Total number of files on this disk, 0xFFFF means "see ZIP64 EOCD record"
			$totalFileCount > 0xFFFF ? 0xFFFF : $totalFileCount,
			// Total number of files, 0xFFFF means "see ZIP64 EOCD record"
			$totalFileCount > 0xFFFF ? 0xFFFF : $totalFileCount,
			// Central directory size, 0xFFFFFFFF means "see ZIP64 EOCD record"
			$centralDirectorySize > 0xFFFFFFFF ? 0xFFFFFFFF : $centralDirectorySize,
			// Offset of start of central directory, 0xFFFFFFFF means "see ZIP64 EOCD record"
			$this->nextFileToAddOffset > 0xFFFFFFFF ? 0xFFFFFFFF : $this->nextFileToAddOffset,
			0 // Zipfile comment length
		);
	}

	/**
	 * @return int
	 */
	private function getVersionNeededToExtract()
	{
		return $this->shallUseZip64Format()
			? self::VERSION_NEEDED_TO_EXTRACT_ZIP64
			: self::VERSION_NEEDED_TO_EXTRACT_PLAIN;
	}

	/**
	 * @return bool
	 */
	private function shallUseZip64Format()
	{
		return $this->shallForceZip64Format || $this->nextFileToAddOffset > 0xFFFFFFFF;
	}

	/**
	 * @param int $number
	 * @return string
	 */
	private function pack64bitUnsignedLE( $number )
	{
		return \pack('VV', $number & 0xFFFFFFFF, ($number & 0xFFFFFFFF00000000) >> 32);
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
}
