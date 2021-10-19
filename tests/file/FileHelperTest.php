<?php
namespace XAF\file;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;;
use XAF\test\DummyFileCreator;

/**
 * @covers \XAF\file\FileHelper
 *
 * Some tests break on Linux because  the implementation falls back the "mv" shell command because of a PHP bug.
 * And of course the shell can't access the vfs.
 */
class FileHelperTest extends TestCase
{
	static private $workPath;

	/** @var DummyFileCreator */
	private $dummyFileCreator;

	/**
	 * @var FileHelper
	 */
	protected $object;

	protected function setUp(): void
	{
		vfsStream::setup('work');
		self::$workPath = vfsStream::url('work');
		$this->dummyFileCreator = new DummyFileCreator(self::$workPath);

		$this->object = new FileHelper();

		// See SUT. This disables a special work-around for a PHP bug that does interfere with testing
		if( !defined('DISABLE_DIRECTORY_MOVE_BUG_WORKAROUND') )
		{
			define('DISABLE_DIRECTORY_MOVE_BUG_WORKAROUND', true);
		}
	}

	// =============================================================================================
	// Directory Creation
	// =============================================================================================

	public function testCreateDirectoryIfNotExistsCreatesDirectory()
	{
		$dirname = self::$workPath . '/newdir';

		$this->object->createDirectoryIfNotExists($dirname);

		$this->assertFileExists($dirname);
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testCreateDirectoryIfNotExistsDoesNotFailForExistentDirectory()
	{
		$this->object->createDirectoryIfNotExists(self::$workPath);
	}

	public function testCreateDirectoryCreatesDirectory()
	{
		$dirname = self::$workPath . '/newdir';

		$this->object->createDirectory($dirname);

		$this->assertFileExists($dirname);
	}

	public function testCreateDirectoryFailsForExistentDirectory()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->createDirectory(self::$workPath);
	}

	public function testCreateDirectoryFailsForUncreatableDirectory()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->createDirectory(self::$workPath . '/nonexistent/newdir');
	}

	public function testCreateDirectoryDeep()
	{
		$dirname = $this->createUnaccessibleDirectory() . '/newdir';

		$this->expectException(\XAF\file\FileError::class);
		$this->object->createDirectoryDeepIfNotExists($dirname);
	}

	public function testCreateDirectoryDeepCreatesMultipleDirectories()
	{
		$dirname = self::$workPath . '/newdir/sub/bottom';

		$this->object->createDirectoryDeepIfNotExists($dirname);

		$this->assertFileExists($dirname);
	}

	// =============================================================================================
	// File & Directory Existence
	// =============================================================================================

	public function testFileExists()
	{
		$this->dummyFileCreator->createFile('foo.bar');

		$result = $this->object->fileExists(self::$workPath . '/foo.bar');

		$this->assertTrue($result);
	}

	public function testFileNotExists()
	{
		$result = $this->object->fileExists(self::$workPath . '/foo.bar');

		$this->assertFalse($result);
	}

	public function testIsFile()
	{
		$this->dummyFileCreator->createFile('foo.bar');

		$result = $this->object->isFile(self::$workPath . '/foo.bar');

		$this->assertTrue($result);
	}

	public function testIsNoFile()
	{
		$result = $this->object->isFile(self::$workPath);

		$this->assertFalse($result);
	}

	public function testIsFileReturnsFalseForMissingFile()
	{
		$result = $this->object->isFile(self::$workPath . '/missing.file');

		$this->assertFalse($result);
	}

	public function testIsDirectory()
	{
		$result = $this->object->isDirectory(self::$workPath);

		$this->assertTrue($result);
	}

	public function testIsNoDirectory()
	{
		$this->dummyFileCreator->createFile('foo.bar');

		$result = $this->object->isDirectory(self::$workPath . '/foo.bar');

		$this->assertFalse($result);
	}

	public function testIsDirectoryReturnsFalseForMissingDirectory()
	{
		$result = $this->object->isDirectory(self::$workPath . '/missing');

		$this->assertFalse($result);
	}

	// missing test is link - link() does not work with vfsstream

	public function testIsNoLink()
	{
		$this->dummyFileCreator->createFile('foo.bar');

		$result = $this->object->isLink(self::$workPath . '/foo.bar');

		$this->assertFalse($result);
	}

	public function testIsLinkReturnsFalseForMissingLink()
	{
		$result = $this->object->isLink(self::$workPath . '/missing.link');

		$this->assertFalse($result);
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testAssertExistsForFile()
	{
		$this->dummyFileCreator->createFile('foo.bar');

		$this->object->assertExists(self::$workPath . '/foo.bar');
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testAssertExistsForDirectory()
	{
		$this->object->assertExists(self::$workPath);
	}

	public function testAssertMissingFileExistsThrowsException()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->expectExceptionMessage('file or directory not found');
		$this->object->assertExists(self::$workPath . '/missing.file');
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testAssertFileExists()
	{
		$this->dummyFileCreator->createFile('foo.bar');

		$this->object->assertFileExists(self::$workPath . '/foo.bar');
	}

	public function testAssertFileExistsThrowsExceptionForNonFile()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->expectExceptionMessage('not a file or link');
		$this->object->assertFileExists(self::$workPath);
	}

	public function testAssertFileExistsThrowsExceptionMissingFile()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->expectExceptionMessage('file or directory not found');
		$this->object->assertFileExists(self::$workPath . '/missing.file');
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testAssertDirectoryExists()
	{
		$this->object->assertDirectoryExists(self::$workPath);
	}

	public function testAssertDirectoryExistsThrowsExceptionForNonDirectory()
	{
		$this->dummyFileCreator->createFile('foo.bar');

		$this->expectException(\XAF\file\FileError::class);
		$this->expectExceptionMessage('not a directory');
		$this->object->assertDirectoryExists(self::$workPath . '/foo.bar');
	}

	public function testAssertDirectoryExistsAssertsFileExistenceBeforeDirectoryCheck()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->expectExceptionMessage('file or directory not found');
		$this->object->assertDirectoryExists(self::$workPath . '/foo.bar');
	}

	public function testAssertDirectoryExistsThrowsExceptionForMissingDirectory()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->expectExceptionMessage('file or directory not found');
		$this->object->assertDirectoryExists(self::$workPath . '/missing');
	}

	// =============================================================================================
	// Directory & File Deletion
	// =============================================================================================

	public function testEmptyDirectoryDeletesFilesAndSubdirs()
	{
		$this->dummyFileCreator->createFile('foo.bar');
		$this->dummyFileCreator->createFile('subdir/foo.bar');

		$this->object->emptyDirectory(self::$workPath);

		$this->assertFileDoesNotExist(self::$workPath . '/subdir/foo.bar');
		$this->assertFileDoesNotExist(self::$workPath . '/subdir');
		$this->assertFileDoesNotExist(self::$workPath . '/foo.bar');
	}

	public function testDeleteRecursivelyIfExistsDeletesExistentFile()
	{
		$this->dummyFileCreator->createFile('foo.bar');

		$this->object->deleteRecursivelyIfExists(self::$workPath . '/foo.bar');

		$this->assertFileDoesNotExist(self::$workPath . '/foo.bar');
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testDeleteRecursivelyIfExistsDoesNotFailForNonExistentDirectory()
	{
		$this->object->deleteRecursivelyIfExists(self::$workPath . '/nonexistent');
	}

	public function testDeleteRecursivelyAlsoDeletesFileInSubdir()
	{
		$this->dummyFileCreator->createFile('sub/foo.bar');

		$this->object->deleteRecursively(self::$workPath . '/sub');

		$this->assertFileDoesNotExist(self::$workPath . '/sub');
	}

	public function testDeleteRecursivelyFailsOnInvalidPath()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->deleteRecursively(self::$workPath . '/nonexistent');
	}

	public function testDeleteDirectoryDeletesEmptyDirectory()
	{
		$directory = self::$workPath . '/subdir';
		$this->object->createDirectory($directory);

		$this->object->deleteDirectory($directory);

		$this->assertFileDoesNotExist($directory);
	}

	public function testDeleteDirectoryFailsOnNonExistentDir()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->deleteDirectory(self::$workPath . '/nonexistent');
	}

	public function testDeleteDirectoryFailsOnNonEmptyDir()
	{
		$this->dummyFileCreator->createFile('sub/foo.bar');

		$this->expectException(\XAF\file\FileError::class);
		$this->object->deleteDirectory(self::$workPath . '/sub');
	}

	public function testDeleteFileIfExistsDeletesFile()
	{
		$this->dummyFileCreator->createFile('foo.bar');

		$this->object->deleteFileIfExists(self::$workPath . '/foo.bar');

		$this->assertFileDoesNotExist(self::$workPath . '/foo.bar');
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testDeleteFileIfExistsDoesNotFailIfFileDoesNotExist()
	{
		$this->object->deleteFileIfExists(self::$workPath . '/nonexistent');
	}

	public function testDeleteFileDeletesFile()
	{
		$this->dummyFileCreator->createFile('foo.bar');

		$this->object->deleteFile(self::$workPath . '/foo.bar');

		$this->assertFileDoesNotExist(self::$workPath . '/foo.bar');
	}

	public function testDeleteFileFailsOnNonExistentFile()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->deleteFile(self::$workPath . '/nonexistent');
	}

	public function testDeleteFileFailsOnUndeletableFile()
	{
		$subDirectory = self::$workPath . '/subdir';
		$this->object->createDirectory($subDirectory);

		$this->expectException(\XAF\file\FileError::class);
		$this->object->deleteFile($subDirectory);
	}

	// =============================================================================================
	// Copy, Move, Rename
	// =============================================================================================

	public function testCopyFileToDirectory()
	{
		$this->dummyFileCreator->createFile('foo.bar');
		$subDirectory = self::$workPath . '/subdir';
		$this->object->createDirectory($subDirectory);

		$this->object->copyFileToDirectory(self::$workPath . '/foo.bar', self::$workPath . '/subdir');

		$this->assertFileExists(self::$workPath . '/subdir/foo.bar');
	}

	public function testCopyFileToDirectoryFailsOnNonExistentSourceFile()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->copyFileToDirectory(self::$workPath . '/nonexistent', self::$workPath);
	}

	public function testCopyFileToDirectoryFailsOnNonExistentTargetDir()
	{
		$this->dummyFileCreator->createFile('foo.bar');

		$this->expectException(\XAF\file\FileError::class);
		$this->object->copyFileToDirectory(self::$workPath . '/foo.bar', self::$workPath . '/nonexistent');
	}

	public function testCopyFileFailsOnNonExistentTargetDir()
	{
		$this->dummyFileCreator->createFile('foo.bar');

		$this->expectException(\XAF\file\FileError::class);
		$this->object->copyFile(self::$workPath . '/foo.bar', self::$workPath . '/nonexistent/new.mp3');
	}

	public function testCopyFileFailsOnNonExistentSourceFile()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->copyFile(self::$workPath . '/nonexistent', self::$workPath . '/new.mp3');
	}

	public function testCopyFileCreatesFileWithGivenName()
	{
		$this->dummyFileCreator->createFile('foo.bar');

		$this->object->copyFile(self::$workPath . '/foo.bar', self::$workPath . '/new.mp3');

		$this->assertFileExists(self::$workPath . '/new.mp3');
	}

	public function testRenameFile()
	{
		$this->dummyFileCreator->createFile('foo.bar');

		$this->object->rename(self::$workPath . '/foo.bar', self::$workPath . '/baz.bam');

		$this->assertFileDoesNotExist(self::$workPath . '/foo.bar');
		$this->assertFileExists(self::$workPath . '/baz.bam');
	}

	public function testRenameNonExistentFileThrowsException()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->move('foo.bar', 'baz.bam');
	}

	public function testRenameFileToNotExistingDirectoryThrowsException()
	{
		$this->dummyFileCreator->createFile('foo.bar');

		$this->expectException(\XAF\file\FileError::class);
		$this->object->move(self::$workPath . '/foo.bar', self::$workPath . '/foo/foo.bar');
	}

	public function testRenameFolder()
	{
		$this->dummyFileCreator->createFile('foo/foo.bar');

		try {
			$this->object->rename(self::$workPath . '/foo', self::$workPath . '/bar');
		} catch( FileError $e ) {
			print_r($e->getDebugInfo());
		}

		$this->assertFileDoesNotExist(self::$workPath . '/foo/foo.bar');
		$this->assertFileExists(self::$workPath . '/bar/foo.bar');
	}

	public function testMoveFile()
	{
		$this->dummyFileCreator->createFile('foo.bar');
		$this->createWorkDirectoryIfNotExists('bar');

		$this->object->move(self::$workPath . '/foo.bar', self::$workPath . '/bar/foo.bar');

		$this->assertFileDoesNotExist(self::$workPath . '/foo.bar');
		$this->assertFileExists(self::$workPath . '/bar/foo.bar');
	}

	public function testMoveDirectory()
	{
		$this->dummyFileCreator->createFile('foo/foo.bar');
		$this->createWorkDirectoryIfNotExists('bar/foo');

		$this->object->move(self::$workPath . '/foo', self::$workPath . '/bar/foo');

		$this->assertFileDoesNotExist(self::$workPath . '/foo/foo.bar');
		$this->assertFileExists(self::$workPath . '/bar/foo/foo.bar');
	}

	public function testMoveSubFolders()
	{
		$this->dummyFileCreator->createFile('foo/bar/foo.bar');
		$this->createWorkDirectoryIfNotExists('bom');

		$this->object->move(self::$workPath . '/foo', self::$workPath . '/bom');

		$this->assertFileExists(self::$workPath . '/bom/bar/foo.bar');
	}

	public function testRenameOverwritesExistingFilesAndFolders()
	{
		$this->dummyFileCreator->createFile('foo/foo.bar');
		$this->dummyFileCreator->createFile('bar/bar.bar');
		$this->dummyFileCreator->createFile('bar/bar/bar.bar');

		$this->object->rename(self::$workPath . '/foo', self::$workPath . '/bar');

		$this->assertFileExists(self::$workPath . '/bar/foo.bar');
		$this->assertFileDoesNotExist(self::$workPath . '/bar/bar.bar');
		$this->assertFileDoesNotExist(self::$workPath . '/bar/bar/bar.bar');
	}

	public function testMoveToDirectory()
	{
		$this->dummyFileCreator->createFile('foo.bar');
		$subDirectory = self::$workPath . '/subdir';
		$this->object->createDirectory($subDirectory);

		$this->object->moveToDirectory(self::$workPath . '/foo.bar', self::$workPath . '/subdir');

		$this->assertFileDoesNotExist(self::$workPath . '/foo.bar');
		$this->assertFileExists(self::$workPath . '/subdir/foo.bar');
	}

	public function testMoveToDirectoryFailsOnNonExistentSourceFile()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->moveToDirectory(self::$workPath . '/nonexistent', self::$workPath);
	}

	public function testMoveToDirectoryFailsOnNonExistentTargetDir()
	{
		$this->dummyFileCreator->createFile('foo.bar');

		$this->expectException(\XAF\file\FileError::class);
		$this->object->moveToDirectory(self::$workPath . '/foo.bar', self::$workPath . '/nonexistent');
	}

	// =============================================================================================
	// File, Directory & Global Metadata
	// =============================================================================================

	// No test for setPermissions() because chmod does not work with vfsstream

	public function testSetModificationTimeSetsCurrentTimeByDefault()
	{
		$file = __DIR__ . '/testfiles/test.jpg';
		$beforeSetting = \time();

		$this->object->setLastModifiedTs($file);

		$afterSetting = \time();
		$this->assertGreaterThanOrEqual($beforeSetting, \filemtime($file));
		$this->assertLessThanOrEqual($afterSetting, \filemtime($file));
	}

	public function testSetPastModificationTime()
	{
		$file = __DIR__ . '/testfiles/test.jpg';
		$timestamp = \strtotime('- 1 week');

		$this->object->setLastModifiedTs($file, $timestamp);

		$this->assertEquals($timestamp, \filemtime($file));
	}

	public function testSetModificationTimeForMissingFileThrowsException()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->setLastModifiedTs('not.existing');
	}

	public function testGetFileSize()
	{
		$this->dummyFileCreator->createFile('30-byte-file', \str_repeat('.', 30));

		$result = $this->object->getFileSize(self::$workPath . '/30-byte-file');

		$this->assertEquals(30, $result);
	}

	public function testFileSizeOfEmptyFile()
	{
		$this->dummyFileCreator->createFile('empty-file', '');

		$result = $this->object->getFileSize(self::$workPath . '/empty-file');

		$this->assertEquals(0, $result);
	}

	public function testGetLastModifiedFromInvalidFileThrowsException()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->getLastModifiedTs(self::$workPath . '/invalid.ext');
	}

	public function testLastModified()
	{
		$this->dummyFileCreator->createFile('file.ext');

		$result = $this->object->getLastModifiedTs(self::$workPath . '/file.ext');

		$this->assertGreaterThanOrEqual(\time() - 1, $result);
	}

	public function testGetFreeBytesBelowFailsForNonExistentDirectory()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->getFreeBytesBelow('/foo1243087234');
	}

	// Can't test free bytes with vfsStream

	// =============================================================================================
	// File Contents
	// =============================================================================================

	public function testAppendToFileFromStringCreatesNewFileIfNotExists()
	{
		$this->object->appendToFileFromString(self::$workPath . '/new.txt', 'Foo');

		$this->assertFileExists(self::$workPath . '/new.txt');
	}

	public function testAppendToExistingFileFromString()
	{
		$textFile = self::$workPath . '/prep.txt';
		$this->object->writeFileFromString($textFile, 'Bar');

		$this->object->appendToFileFromString($textFile, 'Foo');

		$result = $this->object->getFileContents($textFile);
		$this->assertEquals('BarFoo', $result);
	}

	public function testWriteFileFromString()
	{
		$this->object->writeFileFromString(self::$workPath . '/new.txt', 'Foo');

		$this->assertFileExists(self::$workPath . '/new.txt');
	}

	public function testWriteFileFromEmptyStringCreatesEmptyFile()
	{
		$this->object->writeFileFromString(self::$workPath . '/new.txt', '');

		$this->assertFileExists(self::$workPath . '/new.txt');
	}

	public function testGetFileContents()
	{
		$this->dummyFileCreator->createFile('file.ext', 'abc');

		$result = $this->object->getFileContents(self::$workPath . '/file.ext');

		$this->assertEquals('abc', $result);
	}

	public function testOutputFile()
	{
		$this->dummyFileCreator->createFile('some.file', 'foobargl');

		$this->expectOutputString('foobargl');
		$this->object->outputFile(self::$workPath . '/some.file');
	}

	public function testOutputFileFailsOnNonExistentFile()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->outputFile(self::$workPath . '/some.file');
	}

	public function testGetFileContentsFromInvalidFileThrowsException()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->getFileContents('invalid.file');
	}

	public function testGetMd5HashFromFileReturnsMd5Hash()
	{
		$file = 'foo.mp3';
		$this->dummyFileCreator->createFile($file);

		$result = $this->object->getMd5HashFromFile(self::$workPath . '/' . $file);

		$this->assertEquals(32, \strlen($result));
	}

	public function testGetMd5HashFromFileReturnsDifferentValuesOnFileChange()
	{
		$file = 'changing.file';

		\file_put_contents(self::$workPath . '/' . $file, 'Foo');
		$resultA = $this->object->getMd5HashFromFile(self::$workPath . '/' . $file);
		\file_put_contents(self::$workPath . '/' . $file, 'Bar');
		$resultB = $this->object->getMd5HashFromFile(self::$workPath . '/' . $file);

		$this->assertNotEquals($resultA, $resultB);
	}

	public function testGetMd5HashFromInvalidFileThrowsException()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->getMd5HashFromFile('invalid.file');
	}

	// =============================================================================================
	// Test Helpers
	// =============================================================================================

	/**
	 * @param string $directory
	 */
	private function createWorkDirectoryIfNotExists( $directory )
	{
		$path = self::$workPath . '/' . $directory;
		if( !\file_exists($path) )
		{
			\mkdir($path, 0777, true);
		}
	}

	/**
	 * @return string
	 */
	private function createUnaccessibleDirectory()
	{
		$unaccessableDir = self::$workPath . '/unwrite';
		\mkdir($unaccessableDir, '000');
		return $unaccessableDir;
	}
}
