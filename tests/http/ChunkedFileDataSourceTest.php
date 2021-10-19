<?php
namespace XAF\http;

use PHPUnit\Framework\TestCase;
use Phake;

use XAF\file\ProgressiveFileReader;

/**
 * @covers \XAF\http\ChunkedFileDataSource<extended>
 */
class ChunkedFileDataSourceTest extends TestCase
{
	const MIME_TYPE = 'application/foobar';
	const FILE_NAME = 'name.ext';

	/** @var ChunkedFileDataSource */
	private $object;

	/** @var ProgressiveFileReader */
	private $progressiveFileReaderMock;

	protected function setUp(): void
	{
		$this->progressiveFileReaderMock = Phake::mock(ProgressiveFileReader::class);
		$this->object = new ChunkedFileDataSource($this->progressiveFileReaderMock, self::MIME_TYPE, self::FILE_NAME);
	}

	public function testMimeTypeReflectsConstructorArg()
	{
		$this->assertEquals(self::MIME_TYPE, $this->object->getMimeType());
	}

	public function testFileNameReflectsConstructorArg()
	{
		$this->assertEquals(self::FILE_NAME, $this->object->getFileName());
	}

	public function testGetLengthDelegatesToFileReader()
	{
		Phake::when($this->progressiveFileReaderMock)->getSize()->thenReturn(1234);

		$this->assertEquals(1234, $this->object->getLength());
	}

	public function testGetChunkDelegatesToFileReader()
	{
		Phake::when($this->progressiveFileReaderMock)->read(Phake::anyParameters())->thenReturn('chunk');

		$this->assertEquals('chunk', $this->object->getChunk(10));
		Phake::verify($this->progressiveFileReaderMock)->read(10);
	}

	public function testIsEndReachedDelegatesToFileReader()
	{
		Phake::when($this->progressiveFileReaderMock)->isEndOfFile()
			->thenReturn(false)
			->thenReturn(true);

		$this->assertFalse($this->object->isEndReached());
		$this->assertTrue($this->object->isEndReached());
	}
}
