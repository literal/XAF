<?php
namespace XAF\contentserve;

/**
 * The CachingContentServer calls an implementation of this interface to access the content to be cached and delivered.
 *
 * The resource ID can really be anything - it is up the the implementation to make sense of it.
 * It could e. g. be a plain ID number or a HTTP query string type combination of multiple fields
 */
interface ContentProvider
{
	/**
	 * @param string $resourceId
	 * @return ResourceInfo
	 */
	public function getResourceInfo( $resourceId );

	/**
	 * @param string $resourceId
	 * @param string|null $targetFile Target null means output directly to stdout
	 */
	public function writeResourceTo( $resourceId, $targetFile = null );
}

