<?php
namespace XAF\file;

use PHPUnit\Framework\TestCase;
use Phake;

/**
 * @covers \XAF\file\ImageFileHelper
 */
class ImageFileHelperTest extends TestCase
{
	const TEST_IMAGE_FILE = 'test.jpg';
	const TEST_IMAGE_FILE_SIZE_BYTES = 8088;
	const TEST_IMAGE_FILE_WIDTH = 200;
	const TEST_IMAGE_FILE_HEIGHT = 229;
	const TEST_IMAGE_FILE_TYPE = 'image/jpeg';
	const TEST_NON_IMAGE_FILE = 'foo.mp3';

	/** @var ImageFileHelper */
	protected $object;

	/** @var FileHelper */
	private $fileHelperMock;

	/** @var string */
	private $testFilePath;

	protected function setUp(): void
	{
		$this->fileHelperMock = Phake::mock(FileHelper::class);

		$this->object = new ImageFileHelper($this->fileHelperMock);

		$this->testFilePath = __DIR__ . '/testfiles';
	}

	public function testGetImageFileInformationReturnsArray()
	{
		$file = $this->testFilePath . '/' . self::TEST_IMAGE_FILE;
		Phake::when($this->fileHelperMock)->fileExists($this->equalTo($file))->thenReturn(true);

		$result = $this->object->getImageFileInformation($file);

		$this->assertTrue(\is_array($result));
	}

	public function testGetImageFileInformationOfNonImageFileThrowsException()
	{
		$file = $this->testFilePath . '/' . self::TEST_NON_IMAGE_FILE;

		$this->expectException(\XAF\file\FileError::class);
		$this->object->getImageFileInformation($file);
	}

	public function testGetImageFileInformationReturnsWidthHeightSizeAndType()
	{
		$file = $this->testFilePath . '/' . self::TEST_IMAGE_FILE;
		Phake::when($this->fileHelperMock)->fileExists($this->equalTo($file))->thenReturn(true);
		Phake::when($this->fileHelperMock)->getFileSize($this->equalTo($file))->thenReturn(self::TEST_IMAGE_FILE_SIZE_BYTES);
		$expectedResult = [
			'width' => self::TEST_IMAGE_FILE_WIDTH,
			'height' => self::TEST_IMAGE_FILE_HEIGHT,
			'sizeBytes' => self::TEST_IMAGE_FILE_SIZE_BYTES,
			'mimeType' => self::TEST_IMAGE_FILE_TYPE
		];

		$result = $this->object->getImageFileInformation($file);

		$this->assertEquals($expectedResult, $result);
	}


	public function testNotExistingFileIsNoImageFile()
	{
		$file = $this->testFilePath . '/missing.jpg';
		Phake::when($this->fileHelperMock)->fileExists($this->equalTo($file))->thenReturn(false);

		$result = $this->object->isImageFile($file);

		$this->assertFalse($result);
	}

	public function testInvalidFileIsNoImageFile()
	{
		$file = $this->testFilePath . '/' . self::TEST_NON_IMAGE_FILE;
		Phake::when($this->fileHelperMock)->fileExists($this->equalTo($file))->thenReturn(true);

		$result = $this->object->isImageFile($file);

		$this->assertFalse($result);
	}

	public function testImageFileIsImageFile()
	{
		$file = $this->testFilePath . '/' . self::TEST_IMAGE_FILE;
		Phake::when($this->fileHelperMock)->fileExists($this->equalTo($file))->thenReturn(true);

		$result = $this->object->isImageFile($file);

		$this->assertTrue($result);
	}

	public function testCreateImageFromNonImageFileThrowsException()
	{
		$file = $this->testFilePath . '/' . self::TEST_NON_IMAGE_FILE;
		Phake::when($this->fileHelperMock)->assertFileExists($this->equalTo($file))->thenReturn(true);

		$this->expectException(\XAF\file\FileError::class);
		$this->object->createImageFromFile($file);
	}

	public function testCreateImageFromFile()
	{
		$file = $this->testFilePath . '/' . self::TEST_IMAGE_FILE;
		$expectedContent = 'content';
		$this->setImageFileContainsData($file, $expectedContent);

		$result = $this->object->createImageFromFile($file);

		$this->assertInstanceOf('XAF\\type\\Image', $result);
		$this->assertEquals($expectedContent, $result->data);
		$this->assertEquals('image/jpeg', $result->mimeType);
	}

	public function testCreateImageFromFileAllowsForTypeAndDescription()
	{
		$file = $this->testFilePath . '/' . self::TEST_IMAGE_FILE;
		$this->setImageFileContainsData($file, 'content');
		$expectedType = 'Foo';
		$expectedDescription = 'Foo';

		$result = $this->object->createImageFromFile($file, $expectedType, $expectedDescription);

		$this->assertEquals($expectedType, $result->purpose);
		$this->assertEquals($expectedDescription, $result->description);
	}

	/**
	 * @param string $file
	 * @param string $data
	 */
	private function setImageFileContainsData( $file, $data )
	{
		Phake::when($this->fileHelperMock)->assertFileExists($this->equalTo($file))->thenReturn(true);
		Phake::when($this->fileHelperMock)->getFileContents($this->equalTo($file))->thenReturn($data);
	}

}
