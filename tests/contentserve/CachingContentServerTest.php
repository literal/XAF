<?php
namespace XAF\contentserve;

require_once __DIR__ . '/ContentServerTestBase.php';

use Phake;

use XAF\contentserve\FileCache;
use XAF\http\FileSender;

/**
 * @covers \XAF\contentserve\CachingContentServer
 */
class CachingContentServerTest extends ContentServerTestBase
{
	/** @var CachingContentServer */
	private $object;

	/** @var CachingContentServer */
	private $cacheMock;

	/** @var FileSender */
	protected $fileSenderMock;

	const MOCK_CACHE_HANDLE = 7;
	const MOCK_CACHE_FILE = '/path/to/cache/file';

	protected function setUp(): void
	{
		parent::setUp();
		$this->cacheMock = Phake::mock(FileCache::class);
		$this->fileSenderMock = Phake::mock(FileSender::class);
		$this->object = new CachingContentServer($this->contentProviderMock, $this->cacheMock, $this->fileSenderMock);
	}

	public function testDeliverContentChecksCacheAndServesFromThereIfCurrent()
	{
		$this->setResourceExists();
		$this->setCacheHasCurrentVersionOfResource();

		$this->object->deliverContent(self::MOCK_RESOURCE_ID);

		$this->assertResourceWasNotWritten();
		$this->assertFileWasSent(self::MOCK_CACHE_FILE, self::MOCK_RESOURCE_MIME_TYPE);
	}

	public function testDeliverContentChecksCacheAndReCreatesIfNotCurrent()
	{
		$this->setResourceExists();
		$this->setCacheDoesNotHaveCurrentVersionOfResource();

		$this->object->deliverContent(self::MOCK_RESOURCE_ID);

		$this->assertResourceWasWrittenTo(self::MOCK_RESOURCE_ID, $targetFile = self::MOCK_CACHE_FILE);
		$this->assertFileWasSent(self::MOCK_CACHE_FILE, self::MOCK_RESOURCE_MIME_TYPE);
		$this->assertCacheFileLockWasReleased();
	}

	public function testDeliveringNonExistentResourceThrowsException()
	{
		$this->setResourceDoesNotExist();

		$this->expectException(\XAF\contentserve\ResourceNotFoundError::class);
		$this->object->deliverContent(self::MOCK_RESOURCE_ID);
	}

	// ===========================================================================================
	// Mock setup and verification
	// ===========================================================================================

	private function setCacheHasCurrentVersionOfResource( $expectedResourceId = self::MOCK_RESOURCE_ID,
		$expectedTimestamp = self::MOCK_RESOURCE_TIMESTAMP )
	{
		$this->expectCacheQuery($expectedResourceId, $expectedTimestamp, true);
	}

	private function setCacheDoesNotHaveCurrentVersionOfResource( $expectedResourceId = self::MOCK_RESOURCE_ID,
		$expectedTimestamp = self::MOCK_RESOURCE_TIMESTAMP )
	{
		$this->expectCacheQuery($expectedResourceId, $expectedTimestamp, false);
	}

	private function expectCacheQuery( $expectedResourceId, $expectedTimestamp, $setEntryExists )
	{
		Phake::when($this->cacheMock)->openEntry($expectedResourceId, $expectedTimestamp)
			->thenReturn(self::MOCK_CACHE_HANDLE);
		Phake::when($this->cacheMock)->isLockedForWriting(self::MOCK_CACHE_HANDLE)->thenReturn(!$setEntryExists);
		Phake::when($this->cacheMock)->getCacheFile(self::MOCK_CACHE_HANDLE)->thenReturn(self::MOCK_CACHE_FILE);
	}

	private function assertFileWasSent( $expectedFileName, $expectedMimeType )
	{
		Phake::verify($this->fileSenderMock)->sendFile($expectedFileName, $expectedMimeType);
	}

	private function assertCacheFileLockWasReleased()
	{
		Phake::verify($this->cacheMock)->releaseLock(self::MOCK_CACHE_HANDLE);
	}
}
