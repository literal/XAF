<?php
namespace XAF\contentserve;

require_once __DIR__ . '/ContentServerTestBase.php';

use Phake;

use XAF\http\ResponseHeaderSetter;

/**
 * @covers \XAF\contentserve\DirectContentServer
 */
class DirectContentServerTest extends ContentServerTestBase
{
	/** @var DirectContentServer */
	private $object;

	/** @var ResponseHeaderSetter */
	protected $responseHeaderSetterMock;

	protected function setUp(): void
	{
		parent::setUp();
		$this->responseHeaderSetterMock = Phake::mock(ResponseHeaderSetter::class);
		$this->object = new DirectContentServer($this->contentProviderMock, $this->responseHeaderSetterMock);
	}

	public function testDeliverContentSetsHeadersAndWritesResourceToStdout()
	{
		$this->setResourceExists();

		$this->object->deliverContent(self::MOCK_RESOURCE_ID);

		Phake::verify($this->responseHeaderSetterMock)->setContentType(self::MOCK_RESOURCE_MIME_TYPE);
		$this->assertResourceWasWrittenTo(self::MOCK_RESOURCE_ID, null);
	}

	public function testDeliveringNonExistentResourceThrowsException()
	{
		$this->setResourceDoesNotExist();

		$this->expectException(\XAF\contentserve\ResourceNotFoundError::class);
		$this->object->deliverContent(self::MOCK_RESOURCE_ID);
	}
}
