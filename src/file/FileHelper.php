<?php
namespace XAF\file;

/**
 * Stateless wrapper for general file system features.
 *
 * Not static so it can be mocked/stubbed in tests.
 */
class FileHelper
{
	// =============================================================================================
	// Directory Creation
	// =============================================================================================

	/**
	 * @param string $directory
	 */
	public function createDirectoryIfNotExists( $directory )
	{
		if( !\file_exists($directory) )
		{
			try
			{
				$this->createDirectory($directory);
			}
			catch( FileError $e )
			{
				// Guard against race conditions where another process creates the given directory
				// between the initial file_exists() and the call to createDirectory().
				\clearstatcache();
				if( \file_exists($directory) )
				{
					return;
				}
				throw $e;
			}
		}
	}

	/**
	 * @param string $directory
	 */
	public function createDirectory( $directory )
	{
		if( !@\mkdir($directory) )
		{
			throw new FileError('failed to create directory', $directory);
		}
	}

	/**
	 * Also create any directories "on the way" to the bottom-most directory
	 *
	 * @param string $directory
	 */
	public function createDirectoryDeepIfNotExists( $directory )
	{
		if( !$this->fileExists($directory) )
		{
			try
			{
				$this->createDirectoryDeep($directory);
			}
			catch( FileError $e )
			{
				// Guard against race conditions where another process creates the given directory
				// between the initial file_exists() and the call to createDirectoryDeep().
				\clearstatcache();
				if( \is_dir($directory) )
				{
					return;
				}
				throw $e;
			}
		}
	}

	/**
	 * Also create any directories "on the way" to the bottom-most directory
	 *
	 * @param string $directory
	 */
	public function createDirectoryDeep( $directory )
	{
		if( !@\mkdir($directory, 0777, true) )
		{
			throw new FileError('failed to create directory', $directory);
		}
	}

	// =============================================================================================
	// File & Directory Existence
	// =============================================================================================

	/**
	 * @param string $file
	 * @return bool
	 */
	public function fileExists( $file )
	{
		return \file_exists($file);
	}

	/**
	 * @param string $file
	 * @return bool
	 */
	public function isFile( $file )
	{
		return \is_file($file);
	}

	/**
	 * @param string $file
	 * @return bool
	 */
	public function isDirectory( $file )
	{
		return \is_dir($file);
	}

	/**
	 * @param string $file
	 * @return bool
	 */
	public function isLink( $file )
	{
		return \is_link($file);
	}

	/**
	 * Works for files, directories and links
	 *
	 * @param string $path
	 */
	public function assertExists( $path )
	{
		if( !$this->fileExists($path) )
		{
			throw new FileError('file or directory not found', $path);
		}
	}

	/**
	 * Will also accept symbolic links
	 *
	 * @param string $file
	 */
	public function assertFileExists( $file )
	{
		$this->assertExists($file);
		if( !$this->isFile($file) && !$this->isLink($file) )
		{
			throw new FileError('not a file or link', $file);
		}
	}

	/**
	 * @param string $directory
	 */
	public function assertDirectoryExists( $directory )
	{
		$this->assertExists($directory);
		if( !$this->isDirectory($directory) )
		{
			throw new FileError('not a directory', $directory);
		}
	}

	// =============================================================================================
	// Directory & File Deletion
	// =============================================================================================

	/**
	 * Delete a file or directory including all of its contents and throw no exception if the file/dir
	 * dioes not exist
	 *
	 * @param string $fileOrDirectory
	 */
	public function deleteRecursivelyIfExists( $fileOrDirectory )
	{
		if( $this->fileExists($fileOrDirectory) )
		{
			$this->deleteRecursively($fileOrDirectory);
		}
	}

	/**
	 * Delete a file or directory including all of its contents
	 *
	 * @param string $fileOrDirectory
	 */
	public function deleteRecursively( $fileOrDirectory )
	{
		if( !$this->fileExists($fileOrDirectory) )
		{
			throw new FileError('not found', $fileOrDirectory);
		}

		if( $this->isDirectory($fileOrDirectory) )
		{
			$this->emptyDirectory($fileOrDirectory);
			$this->deleteDirectory($fileOrDirectory);
		}
		else
		{
			$this->deleteFile($fileOrDirectory);
		}
	}

	/**
	 * Delete a directory's contents including any depth of sub-directories
	 *
	 * @param string $directory
	 */
	public function emptyDirectory( $directory )
	{
		$directory = FileNameHelper::normalizePath($directory);
		foreach( $this->getDirectoryContents($directory) as $fileName )
		{
			if( $fileName != '.' && $fileName != '..' )
			{
				$this->deleteRecursively($directory . '/' . $fileName);
			}
		}
	}

	/**
	 * @param string $directory
	 */
	public function deleteDirectory( $directory )
	{
		$this->assertDirectoryExists($directory);
		if( !@\rmdir($directory) )
		{
			throw new FileError('failed to delete directory', $directory);
		}
	}

	/**
	 * Will also delete symbolic links
	 *
	 * @param string $file
	 */
	public function deleteFileIfExists( $file )
	{
		if( $this->fileExists($file) && !$this->isDirectory($file) )
		{
			$this->deleteFile($file);
		}
	}

	/**
	 * Will also delete symbolic links
	 *
	 * @param string $file
	 */
	public function deleteFile( $file )
	{
		$this->assertFileExists($file);
		if( !@\unlink($file) )
		{
			throw new FileError('failed to delete file', $file);
		}
	}

	// =============================================================================================
	// Copy, Move, Rename
	// =============================================================================================

	/**
	 * Copy single file to different directory and new filename
	 *
	 * @param string $sourceFile
	 * @param string $destinationFile
	 */
	public function copyFile( $sourceFile, $destinationFile )
	{
		$this->assertFileExists($sourceFile);
		if( !@\copy($sourceFile, $destinationFile) )
		{
			throw new FileError('failed to copy file to', $destinationFile, 'source: ' . $sourceFile);
		}
	}

	/**
	 * Copy single file to different directory under same file name
	 *
	 * @param string $file
	 * @param string $directory
	 */
	public function copyFileToDirectory( $file, $directory )
	{
		$this->assertDirectoryExists($directory);
		$destinationFile = FileNameHelper::normalizePath($directory) . '/' . FileNameHelper::extractName($file);
		$this->copyFile($file, $destinationFile);
	}

	/**
	 * Move or rename file or directory
	 *
	 * @param string $sourceFile
	 * @param string $destinationFile
	 * @throws FileError
	 */
	public function move( $sourceFile, $destinationFile )
	{
		// Workaround for PHP bug https://bugs.php.net/bug.php?id=54097 (renaming folders across volumes fails on Linux)
		// The constatnt for disabling the workaround is for making tests pass.
		if( \is_dir($sourceFile) && $this->isLinux() && !defined('DISABLE_DIRECTORY_MOVE_BUG_WORKAROUND') )
		{
			$command = 'mv ' . \escapeshellarg($sourceFile) . ' ' . \escapeshellarg($destinationFile) . ' 2>&1';
			\exec($command, $output, $retVal);
			if( $retVal )
			{
				throw new FileError(
					'failed to rename file to',
					$destinationFile,
					[
						'command' => $command,
						'return code' => $retVal,
						'output' => \implode("\n", $output)
					]
				);
			}
		}
		else if( !@\rename($sourceFile, $destinationFile) )
		{
			throw new FileError('failed to rename file to', $destinationFile, 'source: ' . $sourceFile);
		}
	}

	/**
	 * @return bool
	 */
	private function isLinux()
	{
		return \stripos(\PHP_OS, 'Linux') !== false;
	}

	/**
	 * Alias for rename()
	 *
	 * @param string $sourceFile
	 * @param string $destinationFile
	 * @throws FileError
	 */
	public function rename( $sourceFile, $destinationFile )
	{
		$this->move($sourceFile, $destinationFile);
	}

	/**
	 * Move file or directory to different directory under same name
	 *
	 * @param string $file
	 * @param string $directory
	 */
	public function moveToDirectory( $file, $directory )
	{
		$this->assertDirectoryExists($directory);
		$destinationFile = FileNameHelper::normalizePath($directory) . '/' . FileNameHelper::extractName($file);
		$this->rename($file, $destinationFile);
	}

	// =============================================================================================
	// File, Directory & Global Metadata
	// =============================================================================================

	/**
	 * @param string $file
	 * @return int size of file in bytes
	 */
	public function getFileSize( $file )
	{
		$size = @\filesize($file);
		if( $size === false )
		{
			throw new FileError('could not get file size of', $file);
		}
		return $size;
	}

	/**
	 * @param string $file
	 * @param int $modifiedTimestamp
	 * @return boolean
	 */
	public function setLastModifiedTs( $file, $modifiedTimestamp = null )
	{
		if( !$this->fileExists($file) )
		{
			throw new FileError('file not exists', $file);
		}
		if( !@\touch($file, $modifiedTimestamp ?: \time()) )
		{
			throw new FileError('could not set modification time', $file);
		}
	}

	/**
	 * @param string $file
	 * @return int returns Unix timestamp
	 */
	public function getLastModifiedTs( $file )
	{
		$time = @\filemtime($file);
		if( !$time )
		{
			throw new FileError('could not get modification time', $file);
		}
		return $time;
	}

	/**
	 * @param string $file
	 * @param int $fileMode Unix-type file mode, i.e. read, write and execute/scan bits for owner, group and world
	 */
	public function setPermissions( $file, $fileMode )
	{
		$this->assertExists($file);
		if( !@\chmod($file, $fileMode) )
		{
			throw new FileError('unable to change permissions', $file);
		}
	}

	/**
	 * @param string $directory
	 * @return float Number of availabale bytes of storage space in the volume that $directory is contained in
	 */
	public function getFreeBytesBelow( $directory )
	{
		$result = @\disk_free_space($directory);
		if( $result === false )
		{
			throw new FileError('unable to assess number of free bytes below', $directory);
		}
		return $result;
	}

	// =============================================================================================
	// File Contents
	// =============================================================================================

	/**
	 * @param string $file
	 * @param string $contents
	 */
	public function writeFileFromString( $file, $contents )
	{
		$bytesWritten = @\file_put_contents($file, $contents);
		if( $bytesWritten === false )
		{
			throw new FileError('failed to write file', $file);
		}
	}

	/**
	 * @param string $file
	 * @param string $contents
	 */
	public function appendToFileFromString( $file, $contents )
	{
		$bytesWritten = @\file_put_contents($file, $contents, \FILE_APPEND);
		if( $bytesWritten === false )
		{
			throw new FileError('failed to write file', $file);
		}
	}

	/**
	 * @param string $file
	 * @return string
	 */
	public function getFileContents( $file )
	{
		$contents = @\file_get_contents($file);
		if( $contents === false )
		{
			throw new FileError('could not get contents', $file);
		}
		return $contents;
	}

	/**
	 * @param string $file
	 */
	public function outputFile( $file )
	{
		$bytesRead = @\readfile($file);
		if( $bytesRead === false )
		{
			throw new FileError('could not stream file to stdout', $file);
		}
	}

	/**
	 * @param string $file
	 * @return string
	 */
	public function getMd5HashFromFile( $file )
	{
		$hash = @\md5_file($file);
		if( $hash === false )
		{
			throw new FileError('could not get md5 hash', $file);
		}
		return $hash;
	}

	// =============================================================================================
	// Internals
	// =============================================================================================

	/**
	 * @param string $directory
	 * @return array the list of file names in the directory
	 */
	private function getDirectoryContents( $directory )
	{
		$this->assertDirectoryExists($directory);
		$result = @\scandir($directory);
		if( $result === false )
		{
			throw new FileError('failed to read directory contents', $directory);
		}
		return $result;
	}
}
