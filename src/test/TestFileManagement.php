<?php
namespace XAF\test;

/**
 * Extension for PHPUnit\Framework\TestCase instances for cleaning test files after each test.
 */
trait TestFileManagement
{
	static private $testFilePaths = [];

	protected function tearDown(): void
	{
		parent::tearDown();
		self::deleteTestFiles();
	}

	/**
	 * @param string $path
	 */
	static protected function createAndRegisterTestFolder( $path )
	{
		self::deleteRecursivelyIfExists($path);
		\mkdir($path, 0777, true);
		self::clearAndRegisterTestFilePath($path);
	}

	/**
	 * @param string $path
	 */
	static protected function clearAndRegisterTestFilePath( $path )
	{
		if( !\in_array($path, self::$testFilePaths) )
		{
			self::$testFilePaths[] = $path;
		}
	}

	static protected function deleteTestFiles()
	{
		foreach( self::$testFilePaths as $path )
		{
			self::deleteRecursivelyIfExists($path);
		}
		self::$testFilePaths = [];
	}

	/**
	 * @param string $sourceDirOrFile
	 * @param string $destinationDirOrFile
	 */
	static protected function copyRecursively( $sourceDirOrFile, $destinationDirOrFile )
	{
		if( \is_dir($sourceDirOrFile) )
		{
			if( !\file_exists($destinationDirOrFile) )
			{
				\mkdir($destinationDirOrFile);
			}
			foreach( \scandir($sourceDirOrFile) as $file )
			{
				if( $file != '.' && $file != '..' )
				{
					self::copyRecursively($sourceDirOrFile . '/' . $file, $destinationDirOrFile . '/' . $file);
				}
			}
		}
		else
		{
			\copy($sourceDirOrFile, $destinationDirOrFile);
		}
	}

	/**
	 * @param string $path
	 */
	static protected function deleteRecursivelyIfExists( $path )
	{
		if( \file_exists($path) )
		{
			self::deleteRecursively($path);
		}
	}

	/**
	 * @param string $path
	 */
	static protected function deleteRecursively( $path )
	{
		if( \is_dir($path) )
		{
			foreach( \scandir($path) as $file )
			{
				if( $file != '.' && $file != '..' )
				{
					self::deleteRecursively($path . '/' . $file);
				}
			}
			\rmdir($path);
		}
		else
		{
			\unlink($path);
		}
	}
}
