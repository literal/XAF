<?php
namespace XAF\contentserve;

class CacheUpdater
{
	/** @var ContentProvider */
	protected $contentProvider;

	/** @var FileCache */
	protected $cache;

	/**
	 * @param ContentProvider $contentProvider object providing access to the content to be served
	 * @param FileCache $cache
	 */
	public function __construct( ContentProvider $contentProvider, FileCache $cache )
	{
		$this->contentProvider = $contentProvider;
		$this->cache = $cache;
	}

	/**
	 * Pre-warm the cache entry for a resource, if the current version of resource is not cached already.
	 *
	 * Can be used to eliminate the delay to the first client who fetches a resource after it has been
	 * created or changed.
	 *
	 * @param string $resourceId
	 */
	public function updateCacheEntry( $resourceId )
	{
		$resourceInfo = $this->contentProvider->getResourceInfo($resourceId);
		if( $resourceInfo->exists )
		{
			$this->getOrCreateCacheFile($resourceInfo);
		}
	}

	/**
	 * Check for current cache entry for the resource and create the entry if it does not exist
	 *
	 * @param ResourceInfo $resourceInfo
	 * @return string full path and name of the cache file
	 */
	protected function getOrCreateCacheFile( ResourceInfo $resourceInfo )
	{
		$cacheHandle = $this->cache->openEntry($resourceInfo->id, $resourceInfo->lastModifiedTimestamp);
		if( $this->cache->isLockedForWriting($cacheHandle) )
		{
			$this->contentProvider->writeResourceTo($resourceInfo->id, $this->cache->getCacheFile($cacheHandle));
			$this->cache->releaseLock($cacheHandle);
		}
		$this->cache->getCacheFile($cacheHandle);
	}
}
