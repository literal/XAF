<?php
namespace XAF\contentserve;

interface ContentServer
{
	/**
	 * Send content to the client
	 *
	 * @param string $resourceId
	 * @throws ResourceNotFoundError
	 */
	public function deliverContent( $resourceId );
}
