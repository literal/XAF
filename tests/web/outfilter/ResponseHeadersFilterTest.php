<?php
namespace XAF\web\outfilter;

use PHPUnit\Framework\TestCase;
use Phake;

use XAF\http\ResponseHeaderSetter;
use XAF\web\Response;

/**
 * @covers \XAF\web\outfilter\ResponseHeadersFilter
 */
class ResponseHeadersFilterTest extends TestCase
{
	/** @var ResponseHeaderSetter */
	private $responseHeaderSetter;

	/** @var ResponseHeadersFilter */
	private $object;

	protected function setUp(): void
	{
		$this->responseHeaderSetter = Phake::mock(ResponseHeaderSetter::class);
		$this->object = new ResponseHeadersFilter($this->responseHeaderSetter);
	}

	public function testExecuteSetsHttpStatusOkAndHtmlMimeTypeByDefault()
	{
		$this->object->execute(new Response);

		Phake::verify($this->responseHeaderSetter)->setResponseCode(200);
		Phake::verify($this->responseHeaderSetter)->setContentType('text/html', null);
	}

	public function testSetStatusCode()
	{
		$this->object->setHttpStatus(401);

		$this->object->execute(new Response);

		Phake::verify($this->responseHeaderSetter)->setResponseCode(401);
	}

	public function testSetMimeType()
	{
		$this->object->setContentType('image/jpg');

		$this->object->execute(new Response);

		Phake::verify($this->responseHeaderSetter)->setContentType('image/jpg', null);
	}

	public function testSetMimeTypeWithEncoding()
	{
		$this->object->setContentType('text/html', 'utf-8');

		$this->object->execute(new Response);

		Phake::verify($this->responseHeaderSetter)->setContentType('text/html', 'utf-8');
	}

	public function testSetContentLanguage()
	{
		$this->object->setContentLanguage('en');

		$this->object->execute(new Response);

		Phake::verify($this->responseHeaderSetter)->setContentLanguage('en');
	}

	public function testSetSendForDownload()
	{
		$this->object->setSendForDownload(true);

		$this->object->execute(new Response);

		Phake::verify($this->responseHeaderSetter)->setIsDownload(null);
	}

	public function testSetSendForDownloadDefineDownloadFileName()
	{
		$this->object->setSendForDownload(true);
		$this->object->setDownloadFileName('foo.xml');

		$this->object->execute(new Response);

		Phake::verify($this->responseHeaderSetter)->setIsDownload('foo.xml');
	}

	public function testSetDownloadFileNameWithoutSendForDownloadHasNoEffect()
	{
		$this->object->setDownloadFileName('foo.xml');

		$this->object->execute(new Response);

		Phake::verify($this->responseHeaderSetter, Phake::never())->setIsDownload();
	}

	public function testHttpCacheControl()
	{
		$this->object->setCacheLifetimeSeconds(0);

		$this->object->execute(new Response);

		Phake::verify($this->responseHeaderSetter)->setCacheability(0, true);
	}

	public function testCacheExpiryHeadersAreSetCorrectly()
	{
		$this->object->setCacheLifetimeSeconds(360);

		$this->object->execute(new Response);

		Phake::verify($this->responseHeaderSetter)->setCacheability(360, true);
	}

	public function testSetPrivateCaching()
	{
		$this->object->setCacheLifetimeSeconds(360);
		$this->object->setAllowPublicCaching(false);

		$this->object->execute(new Response);

		Phake::verify($this->responseHeaderSetter)->setCacheability(360, false);
	}

	public function testSetAllowedCrossSiteXhrOrigin()
	{
		$this->object->setAllowedCrossSiteXhrOrigin('http://foo.com');

		$this->object->execute(new Response);

		Phake::verify($this->responseHeaderSetter)->setAllowedCrossSiteXhrOrigin('http://foo.com');
	}

	public function testAllowedCrossSiteXhrOriginIsNotSetUnlessSpecified()
	{
		$this->object->execute(new Response);

		Phake::verify($this->responseHeaderSetter, Phake::never())
			->setAllowedCrossSiteXhrOrigin(Phake::anyParameters());
	}
}