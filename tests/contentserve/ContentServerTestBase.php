<?php
namespace XAF\contentserve;

use PHPUnit\Framework\TestCase;
use Phake;

use XAF\contentserve\ContentProvider;

abstract class ContentServerTestBase extends TestCase
{
	const MOCK_RESOURCE_ID = 'foobar';
	const MOCK_RESOURCE_TIMESTAMP = 12345;
	const MOCK_RESOURCE_MIME_TYPE = 'mime/type';

	/** @var ContentProvider */
	protected $contentProviderMock;

	protected function setUp(): void
	{
		$this->contentProviderMock = Phake::mock(ContentProvider::class);
	}

	// ===========================================================================================

	protected function setResourceDoesNotExist( $expectedResourceId = self::MOCK_RESOURCE_ID )
	{
		$resourceInfo = new ResourceInfo();
		$resourceInfo->exists = false;
		$this->setResourceInfo($expectedResourceId, $resourceInfo);
	}

	protected function setResourceExists( $expectedResourceId = self::MOCK_RESOURCE_ID )
	{
		$resourceInfo = new ResourceInfo();
		$resourceInfo->exists = true;
		$resourceInfo->id = self::MOCK_RESOURCE_ID;
		$resourceInfo->lastModifiedTimestamp = self::MOCK_RESOURCE_TIMESTAMP;
		$resourceInfo->mimeType = self::MOCK_RESOURCE_MIME_TYPE;
		$this->setResourceInfo($expectedResourceId, $resourceInfo);
	}

	protected function setResourceInfo( $expectedResourceId, ResourceInfo $resourceInfo )
	{
		Phake::when($this->contentProviderMock)->getResourceInfo($expectedResourceId)->thenReturn($resourceInfo);
	}

	// ===========================================================================================

	protected function assertResourceWasWrittenTo( $resourceId, $targetFile )
	{
		Phake::verify($this->contentProviderMock)->writeResourceTo($resourceId, $targetFile);
	}

	protected function assertResourceWasNotWritten()
	{
		Phake::verify($this->contentProviderMock, Phake::never())->writeResourceTo(Phake::anyParameters());
	}
}

