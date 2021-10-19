<?php
namespace XAF\test;

class DummyFileCreator
{
	/** @var string */
	private $rootPath;

	/**
	 * @param string $rootPath
	 */
	public function __construct( $rootPath )
	{
		$rootPath = \rtrim($rootPath, '/\\');
		if( !\file_exists($rootPath) )
		{
			\mkdir($rootPath, 0777, true);
		}
		$this->rootPath = $rootPath;
	}

	/**
	 * @param array $files
	 * @param int $fileSize
	 */
	public function createFiles( array $files, $fileSize = 1 )
	{
		foreach( $files as $file )
		{
			$this->createFile($file, \str_repeat('.', $fileSize));
		}
	}

	/**
	 * @param array $folders
	 */
	public function createFolders( array $folders )
	{
		foreach( $folders as $folder )
		{
			$this->createFolder($folder);
		}
	}

	/**
	 * @param string $file
	 * @param string $fileContents
	 */
	public function createFile( $file, $fileContents = '.' )
	{
		$file = $this->rootPath . '/' . $file;
		$this->createFolderIfNotExist(\dirname($file));
		\file_put_contents($file, $fileContents);
	}

	/**
	 * @param string $folder
	 */
	public function createFolder( $folder )
	{
		$this->createFolderIfNotExist($this->rootPath . '/' . $folder);
	}

	/**
	 * @param string $folder
	 */
	private function createFolderIfNotExist( $folder )
	{
		if( !\file_exists($folder) )
		{
			\mkdir($folder, 0777, true);
		}
	}

	public function emptyRootPath()
	{
		foreach( \scandir($this->rootPath) as $file )
		{
			if( $file != '.' && $file != '..' )
			{
				$this->deepDelete($this->rootPath . '/' . $file);
			}
		}
	}

	/**
	 * @param string $dirOrFile
	 */
	private function deepDelete( $dirOrFile )
	{
		if( \is_dir($dirOrFile) )
		{
			foreach( \scandir($dirOrFile) as $file )
			{
				if( $file != '.' && $file != '..' )
				{
					$this->deepDelete($dirOrFile . '/' . $file);
				}
			}
			\rmdir($dirOrFile);
		}
		else
		{
			\unlink($dirOrFile);
		}
	}
}
