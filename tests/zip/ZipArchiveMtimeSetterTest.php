<?php
namespace XAF\zip;

use PHPUnit\Framework\TestCase;
use XAF\test\TestFileManagement;

use ZipArchive;

/**
 * Only errors are tested here because basic functionality is tested through XZipArchive
 *
 * @covers \XAF\zip\ZipArchiveMtimeSetter
 */
class ZipArchiveMtimeSetterTest extends TestCase
{
	use TestFileManagement;

	/** @var ZipArchiveMtimeSetter */
	private $object;

	/** @var string */
	static private $testPath;

	protected function setUp(): void
	{
		$this->object = new ZipArchiveMtimeSetter();

		self::$testPath = \WORK_PATH . '/ziparchivemtimesetter-test';
		$this->createAndRegisterTestFolder(self::$testPath);
	}

	public function testSettingMtimesOfFilesNotPresentInArchiveThrowsException()
	{
		$archivePath = $this->createZipArchive(['foo.ext' => 'abcde', 'folder/bar.ext' => 'wxyz']);

		$this->expectException(\XAF\zip\ZipArchiveError::class);
		$this->expectExceptionMessage('File(s) not found in archive');
		$this->object->setMtimes(
			$archivePath,
			['folder/bar.ext' => 1444148741, 'folder/foo.ext' => 1444148741, 'FOO.ext' => 1444148741]
		);
	}

	public function testSettingMtimesOnBrokenArchiveFileThrowsException()
	{
		$archivePath = $this->createZipArchive(['foo.ext' => 'abcde', 'bar.ext' => 'wxyz']);
		$this->truncateFileAt($archivePath, \floor(\filesize($archivePath) * 2 / 3));

		$this->expectException(\XAF\zip\ZipArchiveError::class);
		$this->expectExceptionMessage('Unexpected end of file');
		$this->object->setMtimes($archivePath, ['foo.ext' => 1444148741]);
	}

	/**
	 * @param array $files {<file path>: <file contents>, ...}
	 * @return string Full path of the created archive file
	 */
	private function createZipArchive( array $files = [] )
	{
		$archivePath = self::$testPath . '/test.zip';
		$archive = new ZipArchive();
		$archive->open($archivePath, ZipArchive::CREATE);
		foreach( $files as $pathInZip => $fileContents )
		{
			$archive->addFromString($pathInZip, $fileContents);
		}
		$archive->close();
		return $archivePath;
	}

	/**
	 * @param string $filePath
	 * @param int $position
	 */
	private function truncateFileAt( $filePath, $position )
	{
		$fh = \fopen($filePath, 'r+b');
		\ftruncate($fh, $position);
		\fclose($fh);
	}
}
