<?php
namespace XAF\file;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;;

class ProgressiveFileReaderTest extends TestCase
{
	/** @var ProgressiveFileReader */
	private $object;

	/** @var string */
	private $sourceFilePath;

	protected function setUp(): void
	{
		vfsStream::setup('work');
		$this->sourceFilePath = vfsStream::url('work') . '/sourcefile.ext';
		$this->object = new ProgressiveFileReader($this->sourceFilePath);
	}

	public function testGetSizeReturnsFileSize()
	{
		\file_put_contents($this->sourceFilePath, \str_repeat('.', 2007));

		$this->assertEquals(2007, $this->object->getSize());
	}

	public function testGetSizeOfNonExistentFileThrowsException()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->getSize();
	}

	public function testReadReturnsRequestedCorrectFileContents()
	{
		\file_put_contents($this->sourceFilePath, 'abcdef');

		$this->assertSame('abc', $this->object->read(3));
		$this->assertSame('def', $this->object->read(3));
		$this->assertSame('', $this->object->read(3));
	}

	public function testReadReturnsRequestedNumberOfBytesOnlyIfAvailable()
	{
		\file_put_contents($this->sourceFilePath, \str_repeat('.', 2007));

		$this->assertEquals(2000, \strlen($this->object->read(2000)));
		$this->assertEquals(7, \strlen($this->object->read(2000)));
		$this->assertEquals(0, \strlen($this->object->read(2000)));
	}

	public function testReadFromNonExistentFileThrowsException()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->read(1000);
	}

	public function testEndOfFileIsInitiallyFalse()
	{
		$this->assertFalse($this->object->isEndOfFile());
	}

	public function testEndOfFileIsFalseUnlessAllDataHasBeenRead()
	{
		\file_put_contents($this->sourceFilePath, \str_repeat('.', 100));
		$this->object->read(99);

		$this->assertFalse($this->object->isEndOfFile());
	}

	public function testEndOfFileIsTrueWhenAllDataHasBeenRead()
	{
		\file_put_contents($this->sourceFilePath, \str_repeat('.', 100));
		$this->object->read(100);

		$this->assertTrue($this->object->isEndOfFile());
	}

}
