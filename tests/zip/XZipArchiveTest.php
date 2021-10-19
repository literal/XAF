<?php
namespace XAF\zip;

use PHPUnit\Framework\TestCase;
use XAF\test\TestFileManagement;
use XAF\test\PhpUnitTestHelper;

use DateTime;

/**
 * @covers \XAF\zip\XZipArchive
 * @covers \XAF\zip\ZipArchiveMtimeSetter
 */
class XZipArchiveTest extends TestCase
{
	use TestFileManagement;

	const TEST_FOLDER_NAME = 'xziparchive-test';

	/** @var XZipArchive */
	private $object;

	/** @var string */
	static private $testPath;

	public static function setUpBeforeClass(): void
	{
		self::$testPath = \WORK_PATH . '/' . self::TEST_FOLDER_NAME;
	}

	protected function setUp(): void
	{
		$this->object = new XZipArchive();
		$this->createAndRegisterTestFolder(self::$testPath);
	}

	/**
	 * This is a combined smoke test walking through various features because the SUT is mostly just an adapter
	 * for PHP's ZipArchive and we don't want to test the underlying implementation in all detail.
	 */
	public function testWalkThrough()
	{
		$archivePath = self::$testPath . '/test.zip';

		// Create archive, add files and set comments
		$this->object->create($archivePath);
		$this->object->setFileContents('folder1/file1', 'file1 contents');
		$this->object->addFile('folder2/image.jpg', __DIR__ . '/testfiles/test.jpg');
		$this->object->addEmptyFolder('folder3');
		$this->object->setFileComment('folder1/file1', 'file1 comment');
		$this->object->setArchiveComment('quux');
		$this->object->close();

		// Re-open and verify:
		$this->object->open($archivePath);
		// - Archive contents
		$this->assertEquals(
			['folder1/file1', 'folder2/image.jpg', 'folder3/'],
			$this->object->listContents()
		);
		$this->assertTrue($this->object->doesFileExist('folder1/file1'));
		$this->assertFalse($this->object->doesFileExist('folder1/nonexistent.file'));
		// - Access to file contents
		$this->assertEquals('file1 contents', $this->object->getFileContents('folder1/file1'));
		$streamHandle = $this->object->getFileStream('folder1/file1');
		$this->assertEquals('file1 contents', \stream_get_contents($streamHandle));
		\fclose($streamHandle);
		// - Extraction of files
		\mkdir(self::$testPath . '/extract-all');
		$this->object->extractAllTo(self::$testPath . '/extract-all');
		$this->assertFileExists(self::$testPath . '/extract-all/folder1/file1');
		\mkdir(self::$testPath . '/extract-one');
		$this->object->extractFileTo('folder2/image.jpg', self::$testPath . '/extract-one');
		$this->assertFileExists(self::$testPath . '/extract-one/folder2/image.jpg');
		// - File metadata
		$file1Info = $this->object->getFileInfo('folder1/file1');
		$this->assertEquals('folder1/file1', $file1Info['name']);
		$this->assertEquals(\strlen('file1 contents'), $file1Info['size']);
		$this->assertEquals('file1 comment', $this->object->getFileComment('folder1/file1'));

		// Modify archive contents
		$this->object->renameFile('folder1/file1', 'folder1-new/file1-new');
		$this->object->deleteFile('folder2/image.jpg');

		// Re-open and verify modifications
		$this->object->close();
		$this->object->open($archivePath);
		$this->assertEquals(['folder1-new/file1-new', 'folder3/'], $this->object->listContents());

		$this->object->close();
	}

	/**
	 * ATTENTION
	 *
	 * This test fails on Debian-based Linux systems when no timezone is set in php.ini!
	 *
	 * PHP in Debian is tweaked to use the system time zone as a fallback, so usually you don't need to set the time
	 * zone in php.ini. But PHPUnit (as of 9.5) sets the timezone to UTC when it hasn't been set explicitly in php.ini.
	 *
	 * It seems that the ZipArchive implementation behind XZipArchive uses the system's time zone. So then there is a
	 * disagreement about what the local time offset it, which goes badly with the ZIP format storing local time
	 * without time zone information.
	 */
	public function testFileLastModifiedTime()
	{
		$archivePath = self::$testPath . '/test.zip';
		// Attention: Dates must be no earlier than 1980-01-01 and seconds must be even because the
		// ZIP (MS-DOS) timestamp format only has a 2-second resolution
		$folderMtime = $this->dateTimeToUnixTimestamp('2000-01-01 00:00:00');
		$file1Mtime = $this->dateTimeToUnixTimestamp('1982-05-30 22:19:56');
		$file2Mtime = $this->dateTimeToUnixTimestamp('2020-02-12 11:22:34');

		$this->object->create($archivePath);

		// Excercise different ways to set file last modified times
		$this->object->addEmptyFolder('folder', $folderMtime);
		$this->object->setFileContents('file1.ext', 'xyz', $file1Mtime);
		$this->object->setFileContents('file2.ext', 'xyz');
		$this->object->setFileLastModifiedTimestamp('file2.ext', $file2Mtime);

		// Verify modified mtimes are reflected
		$this->assertEquals($folderMtime, $this->object->getFileInfo('folder/')['mtime']);
		$this->assertEquals($file1Mtime, $this->object->getFileInfo('file1.ext')['mtime']);
		$this->assertEquals($file2Mtime, $this->object->getFileInfo('file2.ext')['mtime']);

		// Verify modified mtimes survive renames
		$this->object->renameFile('file1.ext', 'file1-new.ext');
		$this->assertEquals($file1Mtime, $this->object->getFileInfo('file1-new.ext')['mtime']);

		// Re-open and verify mtimes have been stored
		$this->object->close();
		$this->object->open($archivePath);
		//$this->assertEquals($folderMtime, $this->object->getFileInfo('folder/')['mtime']);
		//$this->assertEquals($file1Mtime, $this->object->getFileInfo('file1-new.ext')['mtime']);
		$this->assertEquals($file2Mtime, $this->object->getFileInfo('file2.ext')['mtime']);

		$this->object->close();
	}

	static public function getErrorProducingCallsWithOpenArchive()
	{
		return [
			['extractFileTo', ['non-existent', \WORK_PATH . '/' . self::TEST_FOLDER_NAME . '/target']],
			['getFileContents', ['non-existent']],
			['getFileStream', ['non-existent']],
			['addFile', [null, '/path/to/non/existent/file-235097zh']],
			['getFileInfo', ['non-existent']],
			['setFileLastModifiedTimestamp', ['non-existent', 1237653812]],
			['getFileComment', ['non-existent']],
			['setFileComment', ['non-existent', 'foo']],
			['renameFile', ['non-existent', 'target']],
			['deleteFile', ['non-existent']],
		];
	}

	/**
	 * @dataProvider getErrorProducingCallsWithOpenArchive
	 */
	public function testErrorsWithOpenArchiveAreThrownAsExceptions( $methodName, array $args = [] )
	{
		$this->object->create(self::$testPath . '/test.zip');

		$this->expectException(\XAF\zip\ZipArchiveError::class);
		\call_user_func_array([$this->object, $methodName], $args);
	}

	static public function getErrorProducingCallsWithUninitializedArchive()
	{
		return [
			['open', ['/path/to/non/existent/file-235097zh']],
			['getArchiveComment', []],
			['setArchiveComment', ['foo']],
			['extractAllTo', [\WORK_PATH . '/' . self::TEST_FOLDER_NAME . '/some/path']],
			['addEmptyFolder', ['folder']],
			['setFileContents', ['file', 'contents']],
			['close', []],
		];
	}

	/**
	 * @dataProvider getErrorProducingCallsWithUninitializedArchive
	 */
	public function testErrorsWithUninitializedArchiveAreThrownAsExceptions( $methodName, array $args = [] )
	{
		$this->expectException(\XAF\zip\ZipArchiveError::class);
		@\call_user_func_array([$this->object, $methodName], $args);
	}

	private function dateTimeToUnixTimestamp( $dateTimeString )
	{
		return (new DateTime($dateTimeString))->getTimestamp();
	}
}
