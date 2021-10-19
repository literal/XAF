<?php
namespace XAF\zip;

use ZipArchive;

/**
 * Adapter for PHP's ZipArchive improving method names, dropping redundant and useless features, introducting proper
 * error handling (exceptions) and adding the missing feature of setting packed file modification timestamps.
 */
class XZipArchive
{
	/** @var ZipArchive */
	private $zip;

	/** @var array {<path in ZIP>: <Unix timestamp>, ...} */
	private $modifiedMtimesByPath = [];

	static private $openErrorMessagesByCode = [
		ZipArchive::ER_EXISTS => 'File already exists',
		ZipArchive::ER_INCONS => 'ZIP archive inconsistent',
		ZipArchive::ER_INVAL => 'Invalid argument',
		ZipArchive::ER_MEMORY => 'Not enough memory',
		ZipArchive::ER_NOENT => 'File not found',
		ZipArchive::ER_NOZIP => 'Not a ZIP archive',
		ZipArchive::ER_OPEN => 'Failed to open file',
		ZipArchive::ER_READ => 'Read error',
		ZipArchive::ER_SEEK => 'Seek error'
	];

	public function __construct()
	{
		$this->zip = new ZipArchive();
	}

	/**
	 * Open existing ZIP archive
	 *
	 * @param type $zipFilePath
	 */
	public function open( $zipFilePath )
	{
		$this->throwIfOpenFailed(
			// Used to set ZipArchive::CHECKCONS, but that produces an error on some ZIP files which other
			// programs (GNU unzip, 7zip) test OK.
			$this->zip->open($zipFilePath),
			'Failed to open ZIP file',
			$zipFilePath
		);
		$this->resetMtimes();
	}

	/**
	 * Create an empty ZIP archive file, file must not already exist
	 *
	 * @param type $zipFilePath
	 */
	public function create( $zipFilePath )
	{
		$this->throwIfOpenFailed(
			$this->zip->open($zipFilePath, ZipArchive::CREATE),
			'Failed to create ZIP file',
			$zipFilePath
		);
		$this->resetMtimes();
	}

	/**
	 * @param int $returnValue
	 * @param string $errorMessage
	 * @param string $zipFilePath
	 */
	private function throwIfOpenFailed( $returnValue, $errorMessage, $zipFilePath )
	{
		if( $returnValue !== true )
		{
			$reason = isset(self::$openErrorMessagesByCode[$returnValue])
				? self::$openErrorMessagesByCode[$returnValue]
				: 'unknown error code #' . $returnValue;
			throw new ZipArchiveError($errorMessage . ' (' . $reason . ')', $zipFilePath);
		}
	}

	/**
	 * @return string
	 */
	public function getArchiveComment()
	{
		return $this->throwIfFalse(
			$this->zip->getArchiveComment(),
			'Failed to read archive comment'
		);
	}

	/**
	 * @param string $comment
	 */
	public function setArchiveComment( $comment )
	{
		$this->throwIfFalse(
			$this->zip->setArchiveComment($comment),
			'Failed to set archive comment'
		);
	}

	/**
	 * @return array All contained files' and folders' paths relative to the archive's root
	 */
	public function listContents()
	{
		$result = [];
		for( $i = 0; $i < $this->zip->numFiles; $i++ )
		{
			$result[] = $this->zip->getNameIndex($i);
		}
		return $result;
	}

	/**
	 * @param string $pathInZip
	 * @return bool
	 */
	public function doesFileExist( $pathInZip )
	{
		return $this->zip->locateName($pathInZip) !== false;
	}

	/**
	 * @param string $pathInZip
	 * @param string $targetPath
	 */
	public function extractFileTo( $pathInZip, $targetPath )
	{
		$this->throwIfFalse(
			$this->zip->extractTo($targetPath, $pathInZip),
			'Failed to extract file from archive',
			['pathInZip' => $pathInZip, 'tagetPath' => $targetPath]
		);
	}

	/**
	 * @param type $targetPath
	 * @param array|string|null $pathOrPathsInZip Extract all if null
	 */
	public function extractAllTo( $targetPath )
	{
		$this->throwIfFalse(
			$this->zip->extractTo($targetPath),
			'Failed to extract archive',
			['tagetPath' => $targetPath]
		);
	}

	/**
	 * @param string $pathInZip
	 * @return string Binary file contents
	 */
	public function getFileContents( $pathInZip )
	{
		return $this->throwIfFalse(
			$this->zip->getFromName($pathInZip),
			'Failed to read file contents from archive',
			['pathInZip' => $pathInZip]
		);
	}

	/**
	 * @param string $pathInZip
	 * @return resource A file pointer
	 */
	public function getFileStream( $pathInZip )
	{
		return $this->throwIfFalse(
			$this->zip->getStream($pathInZip),
			'Failed to get file pointer for file in archive',
			['pathInZip' => $pathInZip]
		);
	}

	/**
	 * @param string $pathInZip
	 */
	public function addEmptyFolder( $pathInZip, $lastModifiedTs = null )
	{
		$pathInZip = \rtrim($pathInZip, '/\\') . '/';
		$this->throwIfFalse(
			$this->zip->addEmptyDir($pathInZip),
			'Failed to add empty directory to archive',
			['pathInZip' => $pathInZip]
		);
		$this->setMtime($pathInZip, $lastModifiedTs);
	}

	/**
	 * Adds or overwrites the given file in the archive
	 *
	 * @param string $pathInZip
	 * @param string $contents Binary file contents
	 * @param int|null $lastModifiedTs
	 */
	public function setFileContents( $pathInZip, $contents, $lastModifiedTs = null )
	{
		$this->throwIfFalse(
			$this->zip->addFromString($pathInZip, $contents),
			'Failed to add file to archive',
			['pathInZip' => $pathInZip]
		);
		$this->setMtime($pathInZip, $lastModifiedTs);
	}

	/**
	 * @param string|null $pathInZip Built from source file if null
	 * @param string $sourceFile
	 */
	public function addFile( $pathInZip, $sourceFile )
	{
		$this->throwIfFalse(
			$this->zip->addFile($sourceFile, $pathInZip),
			'Failed to add file to archive',
			['sourcePath' => $sourceFile, 'pathInZip' => $pathInZip]
		);
		$this->setMtime($pathInZip, null);
	}

	/**
	 * @param string $pathInZip
	 * @return array {name: <string>, index: <int>, crc: <int>, size: <int>, comp_size: <int>, mtime: <int>, comp_method: <int>}
	 */
	public function getFileInfo( $pathInZip )
	{
		$result = $this->throwIfFalse(
			$this->zip->statName($pathInZip),
			'Failed to read file info',
			['pathInZip' => $pathInZip]
		);
		if( isset($this->modifiedMtimesByPath[$pathInZip]) )
		{
			$result['mtime'] = $this->modifiedMtimesByPath[$pathInZip];
		}
		return $result;
	}

	/**
	 * Set last-modified timestamp of file in archive by file path & name
	 *
	 * @param string $pathInZip
	 * @param int $lastModifiedTimestamp Unix timestamp
	 */
	public function setFileLastModifiedTimestamp( $pathInZip, $lastModifiedTimestamp )
	{
		$this->throwIfFalse(
			$this->zip->locateName($pathInZip),
			'File does not exist in archive',
			['pathInZip' => $pathInZip]
		);
		$this->modifiedMtimesByPath[$pathInZip] = $lastModifiedTimestamp;
	}

	/**
	 * @param string $pathInZip
	 * @return string
	 */
	public function getFileComment( $pathInZip )
	{
		return $this->throwIfFalse(
			$this->zip->getCommentName($pathInZip),
			'Failed to read file comment from archive',
			['pathInZip' => $pathInZip]
		);
	}

	/**
	 * @param string $pathInZip
	 * @param string $comment
	 */
	public function setFileComment( $pathInZip, $comment )
	{
		$this->throwIfFalse(
			$this->zip->setCommentName($pathInZip, $comment),
			'Failed to set file comment in archive'
		);
	}

	/**
	 * @param string $sourcePathInZip
	 * @param string $targetPathInZip
	 */
	public function renameFile( $sourcePathInZip, $targetPathInZip )
	{
		$this->throwIfFalse(
			$this->zip->renameName($sourcePathInZip, $targetPathInZip),
			'Failed to rename file in archive',
			['pathInZip' => $sourcePathInZip, 'targetPathInZip' => $targetPathInZip]
		);
		$modTime = isset($this->modifiedMtimesByPath[$sourcePathInZip]) ? $this->modifiedMtimesByPath[$sourcePathInZip] : null;
		$this->setMtime($sourcePathInZip, null);
		$this->setMtime($targetPathInZip, $modTime);
	}

	/**
	 * @param string $pathInZip
	 */
	public function deleteFile( $pathInZip )
	{
		$this->throwIfFalse(
			$this->zip->deleteName($pathInZip),
			'Failed to delete file in archive',
			['pathInZip' => $pathInZip]
		);
		$this->setMtime($pathInZip, null);
	}

	public function close()
	{
		$zipFile = $this->zip->filename;
		$this->throwIfFalse($this->zip->close(), 'Failed to close/write archive');
		if( $this->modifiedMtimesByPath )
		{
			$this->applyModifiedMtimes($zipFile, $this->modifiedMtimesByPath);
		}
	}

	/**
	 * @param mixed $returnValue The value returned by a call to a ZipArchive method which would be false
	 *     in case of an error
	 * @param string $errorMessage The main exception message to use if an exception is thrown
	 * @param array $params Various set of parameterrs providing the error context (all fields optional)
	 *     {archivePath: <string>, pathInZip: <string>, targetPathInZip: <string>, sourcePath: <string>, targetPath: <string>}
	 * @return mixed The ZipArchive return value passed in as $returnValue
	 */
	private function throwIfFalse( $returnValue, $errorMessage, array $params = [] )
	{
		if( $returnValue === false )
		{
			$primaryPath = (isset($params['archivePath']) ? $params['archivePath'] : $this->zip->filename)
				. (isset($params['pathInZip']) ? '#' . $params['pathInZip'] : '');
			unset($params['archivePath'], $params['pathInZip']);
			$params['ZipArchive::status'] = $this->zip->status;
			$params['ZipArchive::statusSys'] = $this->zip->statusSys;
			$params['ZipArchive::getStatusString'] = $this->zip->getStatusString();
			throw new ZipArchiveError($errorMessage, $primaryPath, $params);
		}
		return $returnValue;
	}

	private function resetMtimes()
	{
		$this->modifiedMtimesByPath = [];
	}

	/**
	 * @param string $pathInZip
	 * @param int|null $unixTimestamp Null to reset
	 */
	private function setMtime( $pathInZip, $unixTimestamp )
	{
		if( $unixTimestamp !== null )
		{
			$this->modifiedMtimesByPath[$pathInZip] = $unixTimestamp;
		}
		else
		{
			unset($this->modifiedMtimesByPath[$pathInZip]);
		}
	}

	private function applyModifiedMtimes( $zipFile, array $modifiedMtimesByPath )
	{
		$mtimeSetter = new ZipArchiveMtimeSetter();
		$mtimeSetter->setMtimes($zipFile, $modifiedMtimesByPath);
	}
}
