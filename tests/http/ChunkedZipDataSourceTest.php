<?php
namespace XAF\http;

use PHPUnit\Framework\TestCase;
use Phake;

use XAF\zip\ProgressiveZipBuilder;

/**
 * @covers \XAF\http\ChunkedZipDataSource<extended>
 */
class ChunkedZipDataSourceTest extends TestCase
{
	const FILE_NAME = 'archive.zip';

	/** @var ChunkedZipDataSource */
	private $object;

	/** @var ProgressiveZipBuilder */
	private $progressiveZipBuilderMock;

	protected function setUp(): void
	{
		$this->progressiveZipBuilderMock = Phake::mock(ProgressiveZipBuilder::class);
		$this->object = new ChunkedZipDataSource($this->progressiveZipBuilderMock, self::FILE_NAME);
	}

	public function testMimeTypeIsZip()
	{
		$this->assertEquals('application/zip', $this->object->getMimeType());
	}

	public function testFileNameReflectsConstructorArg()
	{
		$this->assertEquals(self::FILE_NAME, $this->object->getFileName());
	}

	public function testGetLengthReturnsPredictedArchiveLength()
	{
		Phake::when($this->progressiveZipBuilderMock)->predictArchiveLength()->thenReturn(1234);

		$this->assertEquals(1234, $this->object->getLength());
	}

	public function testSourceChunksFromZipBuilderAreCutAccordingToRequestedMaxSize()
	{
		$this->setZipBuilderReturnsChunks(['first chunk', 'second chunk']);

		$this->assertEquals('first chun', $this->object->getChunk(10));
		$this->assertEquals('ksecond ch', $this->object->getChunk(10));
		$this->assertEquals('unk', $this->object->getChunk(10));
	}

	public function testSourceChunksFromZipBuilderAreCombinedAccordingToRequestedMaxSize()
	{
		$this->setZipBuilderReturnsChunks(['first chunk', 'second chunk']);

		$this->assertEquals('first chunksecond ch', $this->object->getChunk(20));
		$this->assertEquals('unk', $this->object->getChunk(20));
	}

	public function testGetChunksReturnsEmptyStringAfterAllDataHasBeenFetched()
	{
		$this->setZipBuilderReturnsChunks(['first chunk', 'second chunk']);
		$this->object->getChunk(100);

		$this->assertSame('', $this->object->getChunk(100));
	}

	public function testIsEndReachedReturnsFalseUntilAllDataHasBeenFetched()
	{
		$this->setZipBuilderReturnsChunks(['0123456789']);

		$this->assertFalse($this->object->isEndReached());
		$this->object->getChunk(5);
		$this->assertFalse($this->object->isEndReached());
		$this->object->getChunk(5);
		$this->assertTrue($this->object->isEndReached());
	}

	private function setZipBuilderReturnsChunks( array $chunks )
	{
		$nextChunkIndex = 0;

		Phake::when($this->progressiveZipBuilderMock)->getNextChunk()->thenReturnCallback(
			function() use( $chunks, &$nextChunkIndex ) {
				$result = isset($chunks[$nextChunkIndex]) ? $chunks[$nextChunkIndex] : '';
				$nextChunkIndex++;
				return $result;
			}
		);

		Phake::when($this->progressiveZipBuilderMock)->hasMoreChunks()->thenReturnCallback(
			function() use( $chunks, &$nextChunkIndex ) {
				return $nextChunkIndex < \sizeof($chunks);
			}
		);
	}
}
