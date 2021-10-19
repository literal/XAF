<?php
namespace XAF\contentserve;

use XAF\file\FileHelper;
use XAF\file\FileNameHelper;

class FileCache
{
	const LOCKS_SUBFOLDER = '.locks';

	/** @var FileHelper **/
	private $fileHelper;

	/** @var string */
	private $cachePath;

	/** @var int */
	private $maxRetries;

	/** @var int */
	private $retryIntervalUsec;

	/** @var array hashes of information on currently used cache files, indexed by the handle given to the client */
	private $openEntries = [];

	/** @var int */
	private $nextHandle = 1;

	/**
	 * @param FileHelper $fileHelper
	 * @param string $cachePath Where to store
	 * @param int $maxRetries maximum number of attempts to access a locked cache entry before throwing an exception
	 * @param int $retryIntervalUsec microseconds between retries
	 */
	public function __construct( FileHelper $fileHelper, $cachePath, $maxRetries = 10, $retryIntervalUsec = 500000 )
	{
		$this->fileHelper = $fileHelper;
		$this->cachePath = FileNameHelper::normalizePath($cachePath);
		$this->maxRetries = $maxRetries;
		$this->retryIntervalUsec = $retryIntervalUsec;
	}

	public function __destruct()
	{
		$this->closeAllHandles();
	}

	/**
	 * Get handle for a cache entry. If the entry does not yet exist, it will be locked for writing and
	 * shall be created by the caller. The caller determines this by calling isLockedForWriting() and shall
	 * release the lock as soon as possible after writing the cache file by calling releaseLock().
	 *
	 * @param string $resourceId
	 * @param int $lastModifiedTimestamp
	 * @return int The handle to be used for susequent operations on the cache entry
	 */
	public function openEntry( $resourceId, $lastModifiedTimestamp )
	{
		$handle = $this->nextHandle;
		$this->nextHandle++;

		$cacheKey = $this->buildCacheKey($resourceId, $lastModifiedTimestamp);
		$this->openEntries[$handle] = [
			'file' => $this->buildCacheFileName($cacheKey),
			'lockFile' => $this->buildLockFileName($cacheKey),
			'lockHandle' => null
		];

		$this->lockEntryIfNotExists($handle);

		return $handle;
	}

	/**
	 * @param string $resourceId
	 * @param int $lastModifiedTimestamp
	 * @return string
	 */
	private function buildCacheKey( $resourceId, $lastModifiedTimestamp )
	{
		return \md5($resourceId) . '.' . $lastModifiedTimestamp;
	}

	/**
	 * @param string $cacheKey
	 * @return string
	 */
	private function buildCacheFileName( $cacheKey )
	{
		return $this->cachePath . '/'
			. \substr($cacheKey, 0, 2) . '/' . \substr($cacheKey, 2, 2) . '/' . \substr($cacheKey, 4);
	}

	/**
	 * @param string $cacheKey
	 * @return string
	 */
	private function buildLockFileName( $cacheKey )
	{
		return $this->cachePath . '/' . self::LOCKS_SUBFOLDER . '/' . $cacheKey;
	}

	/**
	 * This method behaves differently depending on the cache entry but cannot be split because
	 * it must act atomically.
	 *
	 * - It does nothing if it finds that the cache file exists and can be read
	 * - It creates the cache file and locks it if the entry does not exist.
	 *   In this case the caller must write to the cache file and call call releaseLock() when done.
	 *
	 * @param int $handle
	 */
	private function lockEntryIfNotExists( $handle )
	{
		$cacheFile = $this->getCacheFile($handle);
		$lockFile = $this->getLockFile($handle);

		for( $retryCount = 0; $retryCount < $this->maxRetries; $retryCount++ )
		{
			$fileExists = $this->fileHelper->fileExists($cacheFile);
			$lockExists = $this->fileHelper->fileExists($lockFile);

			if( $fileExists && !$lockExists )
			{
				return;
			}

			if( $retryCount == 0 )
			{
				$this->createLockFolderIfNotExists();
			}

			// Try getting the lock in *any* case:
			// + if the cache file does not exist, we need the lock to safely create it
			// + if the cache file does exist, the lock might be abandoned, because another instance
			//   crashed while writing the cache file or failed to delete the lock file properly
			//   (i. e. the lock file exists, but is not flocked anymore) - we will try to get the lock
			//   to create the cache file again
			$lockHandle = \fopen($lockFile, 'w');
			if( $lockHandle )
			{
				if( \flock($lockHandle, \LOCK_EX | \LOCK_NB) )
				{
					$this->createCacheFolderIfNotExists($cacheFile);
					$this->openEntries[$handle]['lockHandle'] = $lockHandle;
					return;
				}
				\fclose($lockHandle);
			}

			\usleep($this->retryIntervalUsec);
		}

		throw new FileCacheError(
			'lock timeout on ' . $cacheFile .
			' after ' . \round($this->retryIntervalUsec * $this->maxRetries / 1000000, 1) . ' seconds'
		);
	}

	private function createLockFolderIfNotExists()
	{
		$this->fileHelper->createDirectoryDeepIfNotExists($this->cachePath . '/' . self::LOCKS_SUBFOLDER);
	}

	/**
	 * @param string $cacheFile
	 */
	private function createCacheFolderIfNotExists( $cacheFile )
	{
		$directory = \dirname($cacheFile);
		$this->fileHelper->createDirectoryDeepIfNotExists($directory);
	}

	/**
	 * @param int $handle
	 * @return string
	 */
	public function getCacheFile( $handle )
	{
		$this->assertValidHandle($handle);
		return $this->openEntries[$handle]['file'];
	}

	/**
	 * @param int $handle
	 * @return string
	 */
	private function getLockFile( $handle )
	{
		return $this->openEntries[$handle]['lockFile'];
	}

	/**
	 * @param int $handle
	 * @return bool
	 */
	public function isLockedForWriting( $handle )
	{
		$this->assertValidHandle($handle);
		return (bool)$this->openEntries[$handle]['lockHandle'];
	}

	/**
	 * @param int $handle
	 */
	public function releaseLock( $handle )
	{
		$this->assertValidHandle($handle);
		$lockHandle = $this->openEntries[$handle]['lockHandle'];
		if( $lockHandle )
		{
			\flock($lockHandle, \LOCK_UN);
			\fclose($lockHandle);
			$lockFile = $this->getLockFile($handle);
			// This could theoretically fail if another instance catches the lock between unlocking and unlinking
			\unlink($lockFile);
		}

		$this->openEntries[$handle]['lockHandle'] = null;
	}

	/**
	 * Destory all handles, release all locks
	 */
	public function closeAllHandles()
	{
		foreach( $this->openEntries as $handle => $entry )
		{
			$this->releaseLock($handle);
		}
		$this->openEntries = [];
	}

	/**
	 * @param int $handle
	 * @throws FileCacheError if handle not defined
	 */
	private function assertValidHandle( $handle )
	{
		if( !isset($this->openEntries[$handle]) )
		{
			throw new FileCacheError('cache handle not found: ' . $handle);
		}
	}
}
