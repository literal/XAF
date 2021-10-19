<?php
namespace XAF\contentserve;

use XAF\http\FileSender;

/**
 * Cache and deliver dynamically (on-the-fly) created content which is expensive to build
 * but does neither change often nor depend on the client's session (e.g. resized images
 * or extracted audio snippets).
 *
 * This server retrieves the actual content from an object implementing the ContentProvider interface.
 *
 * The resource IDs used to specify the content can really be anything. From simple integers to complete
 * URIs or file paths. It is up to the content provider object to make sense of the IDs.
 * The server just passes them through and uses them for caching (so they should be unique).
 */
class CachingContentServer implements ContentServer
{
	/** @var ContentProvider */
	protected $contentProvider;

	/** @var FileCache */
	protected $cache;

	/** @var FileSender */
	protected $fileSender;

	/**
	 * @param ContentProvider $contentProvider object providing access to the content to be served
	 * @param FileCache $cache
	 * @param FileSender $fileSender
	 */
	public function __construct( ContentProvider $contentProvider, FileCache $cache, FileSender $fileSender )
	{
		$this->contentProvider = $contentProvider;
		$this->cache = $cache;
		$this->fileSender = $fileSender;
	}

	/**
	 * Send content from cache to the client.
	 *
	 * If the cache entry is stale or does not exist, it will be retrieved from the content provider.
	 *
	 * @param string $resourceId
	 */
	public function deliverContent( $resourceId )
	{
		$resourceInfo = $this->contentProvider->getResourceInfo($resourceId);

		if( !$resourceInfo->exists )
		{
			throw new ResourceNotFoundError('resource', $resourceId);
		}

		$cacheFile = $this->getOrCreateCacheFile($resourceInfo);
		$this->fileSender->sendFile($cacheFile, $resourceInfo->mimeType);
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
		return $this->cache->getCacheFile($cacheHandle);
	}
}
