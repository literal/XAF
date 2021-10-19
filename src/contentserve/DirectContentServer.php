<?php
namespace XAF\contentserve;

use XAF\http\ResponseHeaderSetter;

/**
 * This server retieves the actual content from an object implementing the ContentProvider interface.
 *
 * The resource IDs used to specify the content can really be anything. From simple integers to complete
 * URIs or file paths. It is up to the content provider object to make sense of the IDs.
 * The server just passes them through and uses them for caching (so they should be unique).
 */
class DirectContentServer implements ContentServer
{
	/** @var ContentProvider */
	private $contentProvider;

	/** @var ResponseHeaderSetter */
	private $responseHeaderSetter;

	/**
	 * @param ContentProvider $contentProvider object providing access to the content to be served
	 * @param FileSender $responseHeaderSetter
	 */
	public function __construct( ContentProvider $contentProvider, ResponseHeaderSetter $responseHeaderSetter )
	{
		$this->contentProvider = $contentProvider;
		$this->responseHeaderSetter = $responseHeaderSetter;
	}

	/**
	 * Send content to the client. It will be retrieved from the content provider.
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

		$this->responseHeaderSetter->setContentType($resourceInfo->mimeType);
		$this->responseHeaderSetter->setCacheability(0);
		$this->responseHeaderSetter->setLastModified(\time());
		$this->contentProvider->writeResourceTo($resourceInfo->id, null); // Target null means output directly to stdout
	}
}
