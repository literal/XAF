<?php
namespace XAF\file;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\file\TempFolderManager
 */
class TempFolderManagerTest extends TestCase
{
	/** @var TempFolderManager */
	protected $object;

	/** @var FileHelper */
	private $fileHelperMock;

	static private $workPath;

	protected function setUp(): void
	{
		self::$workPath = 'foo://workpath';

		$this->fileHelperMock = $this->getMockBuilder(\XAF\file\FileHelper::class)->getMock();

		$this->object = new TempFolderManager($this->fileHelperMock, self::$workPath);
	}

	public function testCreateTempFolder()
	{
		$this->fileHelperMock
			->expects($this->once())
			->method('createDirectoryDeepIfNotExists')
			->with($this->stringStartsWith(self::$workPath . '/'));

		$this->object->createFolder();
	}

	public function testCreateFolderReturnsFullPath()
	{
		$result = $this->object->createFolder();

		$this->assertMatchesRegularExpression('/foo:\\/\\/workpath\\/.*/', $result);
	}

	public function testCreateFolderUsesPhpFuncUniqid()
	{
		$result = $this->object->createFolder();

		// uniqid creates string with prefix and 13 chars
		$this->assertMatchesRegularExpression('/.*\\/tmp[a-z\\d]{13}/i', $result);
	}

	public function testCleanupOnlyDeletesPreviouslyCreatedFolders()
	{
		$folder1 = $this->object->createFolder();
		$folder2 = $this->object->createFolder();

		$this->fileHelperMock
			->expects($this->at(0))
			->method('deleteRecursivelyIfExists')
			->with($this->equalTo($folder1));
		$this->fileHelperMock
			->expects($this->at(1))
			->method('deleteRecursivelyIfExists')
			->with($this->equalTo($folder2));

		$this->object->cleanup();
	}

}
