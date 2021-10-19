<?php
namespace XAF\contentserve;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\contentserve\CacheUpdater
 */
class CacheUpdaterTest extends TestCase
{

	const MOCK_CACHE_HANDLE = 7;
	const MOCK_CACHE_FILE = '/path/to/cache/file';
	const MOCK_RESOURCE_ID = 'foobar';
	const MOCK_RESOURCE_TIMESTAMP = 12345;
	const MOCK_RESOURCE_MIME_TYPE = 'mime/type';

	/** @var ContentProvider */
	private $contentProviderMock;

	/** @var FileCache */
	private $cacheMock;

	/** @var CacheUpdater */
	private $object;

	protected function setUp(): void
	{
		$this->contentProviderMock = $this->getMockBuilder(\XAF\contentserve\ContentProvider::class)->getMock();
		$this->cacheMock = $this->getMockBuilder(\XAF\contentserve\FileCache::class)
			->disableOriginalConstructor()
			->getMock();
		$this->object = new CacheUpdater($this->contentProviderMock, $this->cacheMock);
	}


	public function testUpdatingCacheForNonExistentResourceDoesNothing()
	{
		$this->setResourceDoesNotExist();

		$this->expectCacheIsNotCalled();

		$this->object->updateCacheEntry(self::MOCK_RESOURCE_ID);
	}

	public function testUpdatingCacheChecksCacheAndDoesNothingIfCurrent()
	{
		$this->setResourceExists();
		$this->setCacheHasCurrentVersionOfResource();
		$this->expectResourceIsNotCreated();

		$this->object->updateCacheEntry(self::MOCK_RESOURCE_ID);
	}

	public function testUpdatingCacheChecksCacheAndReCreatesIfNotCurrent()
	{
		$this->setResourceExists();
		$this->setCacheDoesNotHaveCurrentVersionOfResource();
		$this->expectResourceIsCreated();
		$this->expectCacheFileLockIsReleased();

		$this->object->updateCacheEntry(self::MOCK_RESOURCE_ID);
	}


	// ===========================================================================================
	// Mock methods
	// ===========================================================================================

	private function setResourceDoesNotExist( $expectedResourceId = self::MOCK_RESOURCE_ID )
	{
		$resourceInfo = new ResourceInfo();
		$resourceInfo->exists = false;
		$this->setResourceInfo($expectedResourceId, $resourceInfo);
	}

	private function setResourceExists( $expectedResourceId = self::MOCK_RESOURCE_ID )
	{
		$resourceInfo = new ResourceInfo();
		$resourceInfo->exists = true;
		$resourceInfo->id = self::MOCK_RESOURCE_ID;
		$resourceInfo->lastModifiedTimestamp = self::MOCK_RESOURCE_TIMESTAMP;
		$resourceInfo->mimeType = self::MOCK_RESOURCE_MIME_TYPE;
		$this->setResourceInfo($expectedResourceId, $resourceInfo);
	}

	private function setResourceInfo( $expectedResourceId, ResourceInfo $resourceInfo )
	{
		$method = $this->contentProviderMock
			->expects($this->any())
			->method('getResourceInfo');
		if( $expectedResourceId !== null )
		{
			$method = $method->with($this->equalTo($expectedResourceId));
		}
		$method->will($this->returnValue($resourceInfo));
	}

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
		$this->cacheMock
			->expects($this->once())
			->method('openEntry')
			->with($this->equalTo($expectedResourceId), $this->equalTo($expectedTimestamp))
			->will($this->returnValue(self::MOCK_CACHE_HANDLE));

		$this->cacheMock
			->expects($this->once())
			->method('isLockedForWriting')
			->with($this->equalTo(self::MOCK_CACHE_HANDLE))
			->will($this->returnValue(!$setEntryExists));

		$this->cacheMock
			->expects($this->any())
			->method('getCacheFile')
			->with($this->equalTo(self::MOCK_CACHE_HANDLE))
			->will($this->returnValue(self::MOCK_CACHE_FILE));
	}

	private function expectCacheFileLockIsReleased()
	{
		$this->cacheMock
			->expects($this->once())
			->method('releaseLock')
			->with($this->equalTo(self::MOCK_CACHE_HANDLE));
	}

	private function expectCacheIsNotCalled()
	{
		$this->cacheMock
			->expects($this->never())
			->method('openEntry');
	}

	// ===========================================================================================

	private function expectResourceIsCreated( $resourceId = self::MOCK_RESOURCE_ID, $targetFile = self::MOCK_CACHE_FILE )
	{
		$this->contentProviderMock
			->expects($this->once())
			->method('writeResourceTo')
			->with($this->equalTo($resourceId), $this->equalTo($targetFile));
	}

	private function expectResourceIsNotCreated()
	{
		$this->contentProviderMock
			->expects($this->never())
			->method('writeResourceTo');
	}

}
