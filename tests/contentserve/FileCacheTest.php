<?php
namespace XAF\contentserve;

use PHPUnit\Framework\TestCase;

use XAF\file\FileHelper;

/**
 * This test is sketchy because concurrent cache access - the most important aspect of the implementation - cannot be
 * tested with PhpUnit.
 *
 * In particular it cannot be tested that:
 * - the cache will wait for a locked entry to be released by another process which is writing to it
 * - the cache will capture a stale lock, e.g. when the locking process has crashed (after which the lock file
 *   still exists, but the flock on it should be released)
 *
 * @covers \XAF\contentserve\FileCache
 */
class FileCacheTest extends TestCase
{
	/** @var FileCache */
	protected $object;

	static private $cachePath;

	protected function setUp(): void
	{
		$fileHelper = new FileHelper();

		self::$cachePath = \WORK_PATH . '/file_cache';
		$fileHelper->createDirectoryIfNotExists(self::$cachePath);
		$fileHelper->emptyDirectory(self::$cachePath);

		$maxRetries = 2;
		$retryIntervalUsec = 10;
		$this->object = new FileCache($fileHelper, self::$cachePath, $maxRetries, $retryIntervalUsec);
	}

	protected function tearDown(): void
	{
		$this->object->closeAllHandles();
	}

	static public function tearDownAfterClass(): void
	{
		$fileHelper = new FileHelper();
		$fileHelper->deleteRecursively(self::$cachePath);
	}

	public function testGetCacheHandleReturnsDifferentIntegerOnEachCall()
	{
		$handle1 = $this->object->openEntry('foobar', 12345);
		$handle2 = $this->object->openEntry('barfoo', 12345);

		$this->assertIsInt($handle1);
		$this->assertIsInt($handle2);
		$this->assertNotEquals($handle1, $handle2);
	}


	public function testInvalidHandleThrowsExceptionInGetCacheFile()
	{
		$this->expectException(\XAF\contentserve\FileCacheError::class);
		$this->expectExceptionMessage('handle');
		$this->object->getCacheFile(123);
	}

	public function testInvalidHandleThrowsExceptionInIsLockedForWriting()
	{
		$this->expectException(\XAF\contentserve\FileCacheError::class);
		$this->expectExceptionMessage('handle');
		$this->object->isLockedForWriting(123);
	}

	public function testInvalidHandleThrowsExceptionInReleaseLock()
	{
		$this->expectException(\XAF\contentserve\FileCacheError::class);
		$this->expectExceptionMessage('handle');
		$this->object->releaseLock(123);
	}

	public function testGetCacheFileReturnsFileBelowCachePath()
	{
		$handle = $this->object->openEntry('foobar', 12345);

		$cacheFile = $this->object->getCacheFile($handle);

		$this->assertStringStartsWith(\str_replace('\\', '/', self::$cachePath) . '/', $cacheFile);
	}

	public function testDifferentResourceIdsProduceDifferentCacheFiles()
	{
		$handle1 = $this->object->openEntry('foobar', 12345);
		$handle2 = $this->object->openEntry('barfoo', 12345);

		$this->assertNotEquals($this->object->getCacheFile($handle1), $this->object->getCacheFile($handle2));
	}

	public function testDifferentTimestampsProduceDifferentCacheFilesForSameResourceId()
	{
		$handle1 = $this->object->openEntry('foobar', 12345);
		$handle2 = $this->object->openEntry('foobar', 23456);

		$this->assertNotEquals($this->object->getCacheFile($handle1), $this->object->getCacheFile($handle2));
	}

	public function testIsLockedForWritingReturnsTrueForNonExistentEntry()
	{
		$handle = $this->object->openEntry('foobar', 12345);

		$lockedForWriting = $this->object->isLockedForWriting($handle);

		$this->assertTrue($lockedForWriting);
	}

	public function testIsLockedForWritingReturnsFalseForExistentEntry()
	{
		$this->createCacheEntry('foobar', 12345);

		$handle = $this->object->openEntry('foobar', 12345);
		$lockedForWriting = $this->object->isLockedForWriting($handle);

		$this->assertFalse($lockedForWriting);
	}

	private function createCacheEntry( $resourceId, $lastModifiedTimestamp )
	{
		$handle = $this->object->openEntry($resourceId, $lastModifiedTimestamp);

		$fh = \fopen($this->object->getCacheFile($handle), 'wb');
		\fwrite($fh, 'foo bar boom baz quux');
		\fclose($fh);

		$this->object->releaseLock($handle);
	}

	public function testOpenEntryCreatesLockForNonExistentEntry()
	{
		$this->object->openEntry('foobar', 12345);
		// no call to releaseLock() here!

		// The cache will actually wait for the cache entry to be unlocked, but we cannot test this with PhpUnit
		// because we would need concurrent threads.
		// This setUp() method of this test class sets the retry count and the timeout so low that the cache will
		// almost instantly give up and throw an exception.
		$this->expectException(\XAF\contentserve\FileCacheError::class);
		$this->expectExceptionMessage('timeout');
		$this->object->openEntry('foobar', 12345);
	}
}
