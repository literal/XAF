<?php
namespace XAF\file;

use PHPUnit\Framework\TestCase;
use Phake;

/**
 * @covers \XAF\file\TempFileService
 */
class TempFileServiceTest extends TestCase
{
	const TEMP_FOLDER = '/temp';

	/** @var TempFolderManager */
	protected $tempFolderManagerMock;

	/** @var FileHelper */
	protected $fileHelperMock;

	/** @var TempFileService */
	protected $object;

	protected function setUp(): void
	{
		$this->tempFolderManagerMock = Phake::mock(TempFolderManager::class);
		Phake::when($this->tempFolderManagerMock)->createFolder(Phake::anyParameters())->thenReturn(self::TEMP_FOLDER);
		$this->fileHelperMock = Phake::mock(FileHelper::class);
		$this->object = new TempFileService($this->tempFolderManagerMock, $this->fileHelperMock);
	}

	public function testCreateTemporaryFileCopyCreatesTempFolder()
	{
		$result = $this->object->createTemporaryFileCopy('foo.bar');

		$this->assertStringStartsWith(self::TEMP_FOLDER, $result);
	}

	public function testCreateTemporaryFileCopyCreatesFileCopy()
	{
		$this->object->createTemporaryFileCopy('foo/bar.boom');

		Phake::verify($this->fileHelperMock)->copyFile('foo/bar.boom', $this->stringStartsWith(self::TEMP_FOLDER));
	}

	public function testCreateTemporaryFileLocationUsesExtensionTmpByDefault()
	{
		$result = $this->object->createTemporaryFileLocation();

		$this->assertStringEndsWith('.tmp', $result);
	}

	public function testCreateTemporaryFileLocationAllowsForExtension()
	{
		$result = $this->object->createTemporaryFileLocation('foo');

		$this->assertStringEndsWith('.foo', $result);
	}

	public function testCreateTemporaryFileLocationCreatesNewFolder()
	{
		$this->object->createTemporaryFileLocation();

		Phake::verify($this->tempFolderManagerMock)->createFolder();
	}

	/**
	 * This is the normal case where the initially created temp folder remains and is used for subsequent files
	 */
	public function testTemporaryFolderIsCreatedOnlyOnce()
	{
		Phake::when($this->fileHelperMock)->fileExists(self::TEMP_FOLDER)->thenReturn(true);

		$this->object->createTemporaryFileLocation();
		$this->object->createTemporaryFileCopy('foo.bar');
		$this->object->createTemporaryFileLocation();
		$this->object->createTemporaryFileCopy('foo.bar');

		Phake::verify($this->tempFolderManagerMock)->createFolder();
		Phake::verifyNoFurtherInteraction($this->tempFolderManagerMock);
	}

	/**
	 * This is what happens when TempFolderManager::cleanup() is called before requesting a new temp file
	 */
	public function testNewTemporaryFolderIsCreatedWhenPreviousFolderDoesNotExistAnymore()
	{
		Phake::when($this->fileHelperMock)->fileExists(self::TEMP_FOLDER)->thenReturn(false);

		$this->object->createTemporaryFileLocation();
		$this->object->createTemporaryFileCopy('foo.bar');

		Phake::verify($this->tempFolderManagerMock, Phake::times(2))->createFolder();
	}
}
