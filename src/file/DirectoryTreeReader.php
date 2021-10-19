<?php
namespace XAF\file;

use XAF\file\FileNameHelper;

class DirectoryTreeReader
{
	/** @var FileFilter */
	private $fileFilter;

	public function __construct( FileFilter $fileFilter = null )
	{
		$this->fileFilter = $fileFilter ?: new NullFileFilter();
	}

	/**
	 * Walk through subdirectories recursively and return array of DirEntry objets containing
	 * their absolute file system paths
	 *
	 * @param string $directory
	 * @param array $options
	 * @return DirEntry[]
	 */
	public function getTreeAbsolute( $directory, array $options = [] )
	{
		$directory = FileNameHelper::normalizePath($directory);
		return $this->getDirectoryContentsRecursively('', $directory, $options);
	}

	/**
	 * Walk through subdirectories recursively and return array of DirEntry objets containing
	 * paths relative to the given directory
	 *
	 * @param string $directory
	 * @param array $options
	 * @return DirEntry[]
	 */
	public function getTreeRelative( $directory, array $options = [] )
	{
		$directory = FileNameHelper::normalizePath($directory);
		return $this->getDirectoryContentsRecursively($directory, '', $options);
	}

	/**
	 * Walk through subdirectories recursively starting in directory and return array of DirEntry
	 * objets containing paths relative to the given root directory
	 *
	 * @param string $rootDirectory
	 * @param string $directory
	 * @param array $options
	 * @return DirEntry[]
	 */
	public function getTreeRelativeTo( $rootDirectory, $directory, array $options = [] )
	{
		$rootDirectory = FileNameHelper::normalizePath($rootDirectory);
		$directory = FileNameHelper::normalizePath($directory);
		return $this->getDirectoryContentsRecursively($rootDirectory, $directory, $options);
	}
	/**
	 * @param string $rootPath Common path to which the 'path' properties in the DirEntry shall be relative
	 * @param string $subPath Relative path of the directory below the root path
	 * @param array $options
	 * @return DirEntry[]
	 */
	private function getDirectoryContentsRecursively( $rootPath, $subPath, array $options )
	{
		$result = [];
		$absolutePath = $this->combinePathComponents($rootPath, $subPath);
		foreach( $this->getAllFilesInDirectory($absolutePath) as $fileName )
		{
			$absoluteFilePath = $this->combinePathComponents($absolutePath, $fileName);
			$relativeFilePath = $this->combinePathComponents($subPath, $fileName);
			if( $this->isQualifiedFile($absoluteFilePath, $options) )
			{
				$result[] = new FileDirEntry(
					$this->forceToUtf8($fileName),
					$relativeFilePath,
					\filemtime($absoluteFilePath),
					\filesize($absoluteFilePath)
				);
			}
			else if( $this->isQualifiedFolder($absoluteFilePath, $options) )
			{
				$folderDirEntry = new FolderDirEntry(
					$this->forceToUtf8($fileName),
					$relativeFilePath,
					\filemtime($absoluteFilePath)
				);
				$subOptions = $options;
				$subOptions['maxDepth'] = !isset($subOptions['maxDepth']) ? 10 : $subOptions['maxDepth'] - 1;
				$folderDirEntry->contents = $subOptions['maxDepth'] > 1
					? $this->getDirectoryContentsRecursively($rootPath, $relativeFilePath, $subOptions)
					: [];
				$result[] = $folderDirEntry;
			}
		}
		return $result;
	}

	/**
	 * @param string $string
	 * @return string
	 */
	protected function forceToUtf8( $string )
	{
		return \mb_check_encoding($string, 'UTF-8') ? $string : \utf8_encode($string);
	}

	/**
	 * Walk through subdirectories recursively and return array of all contained files with their absolute paths
	 *
	 * @param string $directory
	 * @param array $options
	 * @return array
	 */
	public function getFlatAbsolute( $directory, array $options = [] )
	{
		$directory = FileNameHelper::normalizePath($directory);
		return $this->getFlatDirectoryContentsRecursively('', $directory, $options);
	}

	/**
	 * Walk through subdirectories recursively and return array of all contained files with their paths relative to
	 * the given directory
	 *
	 * @param string $directory
	 * @param array $options
	 * @return array
	 */
	public function getFlatRelative( $directory, array $options = [] )
	{
		$directory = FileNameHelper::normalizePath($directory);
		return $this->getFlatDirectoryContentsRecursively($directory, '', $options);
	}

	/**
	 * Walk through subdirectories recursively starting in directory and return array of all contained files with their paths relative to
	 * the given directory
	 *
	 * @param string $rootDirectory
	 * @param string $directory
	 * @param array $options
	 * @return array
	 */
	public function getFlatRelativeTo( $rootDirectory, $directory, array $options = [] )
	{
		$rootDirectory = FileNameHelper::normalizePath($rootDirectory);
		$directory = FileNameHelper::normalizePath($directory);
		return $this->getFlatDirectoryContentsRecursively($rootDirectory, $directory, $options);
	}


	/**
	 * @param string $rootPath Common path to which the 'path' properties in the DirEntry shall refer
	 * @param string $subPath Relative path of the directory below the root path
	 * @param array $options
	 * @return array
	 */
	private function getFlatDirectoryContentsRecursively( $rootPath, $subPath, array $options )
	{
		$result = [];
		$absolutePath = $this->combinePathComponents($rootPath, $subPath);
		foreach( $this->getAllFilesInDirectory($absolutePath) as $fileName )
		{
			$absoluteFilePath = $this->combinePathComponents($absolutePath, $fileName);
			$relativeFilePath = $this->combinePathComponents($subPath, $fileName);

			if( $this->isQualifiedFile($absoluteFilePath, $options) )
			{
				$result[] = $relativeFilePath;
			}
			else if( $this->isQualifiedFolder($absoluteFilePath, $options) )
			{
				$subOptions = $options;
				$subOptions['maxDepth'] = !isset($subOptions['maxDepth']) ? 10 : $subOptions['maxDepth'] - 1;
				$subFolderResult = $this->getFlatDirectoryContentsRecursively(
					$rootPath,
					$relativeFilePath,
					$subOptions
				);
				$result = \array_merge($result, $subFolderResult);
			}
		}
		return $result;
	}

	/**
	 * @param string $part1
	 * @param string $part2
	 * @return string
	 */
	private function combinePathComponents( $part1, $part2 )
	{
		return $part1 . ($part1 !== '' && $part2 !== '' ? '/' : '') . $part2;
	}


	/**
	 * @param string $directory
	 * @return array
	 */
	private function getAllFilesInDirectory( $directory )
	{
		$result = @\scandir($directory);
		if( $result === false )
		{
			throw new FileError('failed to read directory', $directory);
		}
		return $result;
	}

	/**
	 * @param string $file
	 * @param array $options
	 * @return boolean
	 */
	private function isQualifiedFile( $file, array $options )
	{
		if( !\is_file($file) )
		{
			return false;
		}

		if( isset($options['maxDepth']) && $options['maxDepth'] === 0 )
		{
			return false;
		}

		if( !$this->fileFilter->doesPass($file) )
		{
			return false;
		}

		if( isset($options['fileFilter']) && !$options['fileFilter']->doesPass($file) )
		{
			return false;
		}

		return true;
	}

	/**
	 * @param string $file
	 * @param array $options
	 * @return boolean
	 */
	private function isQualifiedFolder( $file, array $options )
	{
		if( !\is_dir($file) )
		{
			return false;
		}

		if( isset($options['maxDepth']) && $options['maxDepth'] === 0 )
		{
			return false;
		}

		if( \in_array(FileNameHelper::extractName($file), ['.', '..']) )
		{
			return false;
		}

		if( !$this->fileFilter->doesPass($file) )
		{
			return false;
		}

		if( isset($options['fileFilter']) && !$options['fileFilter']->doesPass($file) )
		{
			return false;
		}

		return true;
	}
}
