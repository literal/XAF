<?php
namespace XAF\zip;

use PHPUnit\Framework\TestCase;
use Phake;
use XAF\test\TestFileManagement;

use XAF\file\FileHelper;
use ZipArchive;

/**
 * Unfortunately, the PHP ZipArchive class does not support ZIP64 format (which alows for ZIP archives beyond 4 GiB)
 * so they cannot really be tested here. Apart from that, archives beyond 4 GiB will only work properly under 64bit
 * PHP builds, anyway.
 */
class ProgressiveZipBuilderTest extends TestCase
{
	use TestFileManagement;

	static private $workPath;

	/** @var ProgressiveZipBuilder */
	private $object;

	/** @var FileHelper */
	private $fileHelperMock;

	static public function setUpBeforeClass(): void
	{
		self::$workPath = \WORK_PATH . '/prog_zip';
	}

	protected function setUp(): void
	{
		self::clearAndRegisterTestFilePath(self::$workPath);
		$this->fileHelperMock = Phake::mock(FileHelper::class);
		$this->object = new ProgressiveZipBuilder($this->fileHelperMock);
	}

	public function testArchiveSizeIsPredictedCorrectly()
	{
		$this->setupSourceFiles([
			['/foo.src', 'foo'],
			['/bar.src', 'barbar'],
		]);
		$this->object->addFile('/foo.src');
		$this->object->addFile('/bar.src');

		$predictedSize = $this->object->predictArchiveLength();

		$this->assertEquals($predictedSize, \strlen($this->getCompleteArchiveSource()));
	}

	public function testArchiveSizeIsPredictedCorrectlyForZip64Format()
	{
		$this->setupSourceFiles([
			['/foo.src', "foo\0boom"],
			['/bar.src', 'barbar'],
		]);
		$this->object->addFile('/foo.src');
		$this->object->addFile('/bar.src');
		$this->object->setForceZip64Format(true);

		$predictedSize = $this->object->predictArchiveLength();

		$this->assertEquals($predictedSize, \strlen($this->getCompleteArchiveSource()));
	}

	public function testGetNextChunkReturnsEmptyStringWhenArchiveIsComplete()
	{
		$this->setupSourceFiles([['/foo.src', 'foo']]);
		$this->object->addFile('/foo.src');
		while( $this->object->hasMoreChunks() )
		{
			$this->object->getNextChunk();
		}

		$this->assertSame('', $this->object->getNextChunk());
	}

	public function testFileContentsAreStoredCorrectly()
	{
		$this->setupSourceFiles([['/file.src', 'the file contents']]);

		$this->object->addFile('/file.src', 'file.dest');

		$this->assertEquals('the file contents', $this->writeArchiveAndReturnFileContents('file.dest'));
	}

	public function testFileMetadataIsStoredCorrectly()
	{
		$this->setupSourceFiles([
			['/foo.src', 'foo'],
			['/bar.src', 'barbar'],
		]);

		$this->object->addFile('/foo.src', 'folder1/file1.xxx', 1358204400);
		$this->object->addFile('/bar.src', 'folder2/file2.xxx', 1358204460);

		$this->assertEquals(
			[
				[
					'name' => 'folder1/file1.xxx',
					'index' => 0,
					'crc' => \crc32('foo'),
					'size' => 3,
					'mtime' => 1358204400,
					'comp_size' => 3,
					'comp_method' => 0,
					'encryption_method' => 0
				],
				[
					'name' => 'folder2/file2.xxx',
					'index' => 1,
					'crc' => \crc32('barbar'),
					'size' => 6,
					'mtime' => 1358204460,
					'comp_size' => 6,
					'comp_method' => 0,
					'encryption_method' => 0
				]
			],
			$this->writeArchiveAndReturnFileDetails()
		);

	}

	/**
	 * @param array $files [[<path>, <contents>], ...]
	 */
	private function setupSourceFiles( array $files )
	{
		foreach( $files as $file )
		{
			$path = $file[0];
			$contents = $file[1];
			Phake::when($this->fileHelperMock)->getFileSize($path)->thenReturn(\strlen($contents));
			Phake::when($this->fileHelperMock)->getFileContents($path)->thenReturn($contents);
		}
	}

	/**
	 * @return string
	 */
	private function getCompleteArchiveSource()
	{
		$result = '';
		while( $this->object->hasMoreChunks() )
		{
			$result .= $this->object->getNextChunk();
		}
		return $result;
	}

	/**
	 * @return array List of file information hashes
	 */
	private function writeArchiveAndReturnFileDetails()
	{
		$zipArchive = $this->writeArchiveToDiskAndOpenAsZipArchive();

		$result = [];
		for( $i = 0; $i < $zipArchive->numFiles; $i++ )
		{
			$result[] = $zipArchive->statIndex($i);
		}

		$zipArchive->close();

		return $result;
	}

	/**
	 * @param string $fileNameInArchive
	 * @return string The file's contents
	 */
	private function writeArchiveAndReturnFileContents( $fileNameInArchive )
	{
		$zipArchive = $this->writeArchiveToDiskAndOpenAsZipArchive();

		$fp = $zipArchive->getStream($fileNameInArchive);
		$result = '';
		while( !\feof($fp) )
		{
			$result .= \fread($fp, 1000);
		}

		$zipArchive->close();

		return $result;
	}


	/**
	 * @return ZipArchive
	 */
	private function writeArchiveToDiskAndOpenAsZipArchive()
	{
		$archiveFile = $this->writeArchiveToDisk();
		$zipArchive = new \ZipArchive();
		$zipArchive->open($archiveFile);
		return $zipArchive;
	}

	/**
	 * @return string Full path to the stored archive
	 */
	private function writeArchiveToDisk()
	{
		if( !\is_dir(self::$workPath) )
		{
			\mkdir(self::$workPath, 0777, true);
		}

		$archiveFile = self::$workPath . '/result.zip';
		\file_put_contents($archiveFile, $this->getCompleteArchiveSource());

		return $archiveFile;
	}
}
