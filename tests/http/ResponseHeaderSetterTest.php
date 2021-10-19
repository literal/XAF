<?php
namespace XAF\http;

use PHPUnit\Framework\TestCase;
use Phake;

/**
 * @covers \XAF\http\ResponseHeaderSetter
 */
class ResponseHeaderSetterTest extends TestCase
{
	/** @var ResponseHeaderSetter */
	private $object;

	/** @var HeaderSender */
	private $headerSenderMock;

	const CURRENT_TIMESTAMP = 1234567890;

	protected function setUp(): void
	{
		$this->headerSenderMock = Phake::mock(HeaderSender::class);
		$this->object = new ResponseHeaderSetter($this->headerSenderMock);
		$this->object->setCurrentTimestamp(self::CURRENT_TIMESTAMP);
	}

	public function testSetResponseCode()
	{
		$this->object->setResponseCode(401);

		Phake::verify($this->headerSenderMock)->setResponseCode(401);
	}

	public function testSetNotModified()
	{
		$this->object->setNotModified();

		Phake::verify($this->headerSenderMock)->setResponseCode(304);
	}

	public function testSetContentType()
	{
		$this->object->setContentType('image/jpg');

		$this->assertHeaderWasSet('Content-Type', 'image/jpg');
	}

	public function testSetContentTypeWithEncoding()
	{
		$this->object->setContentType('text/html', 'utf-8');

		$this->assertHeaderWasSet('Content-Type', 'text/html; charset=utf-8');
	}

	public function testSetContentLength()
	{
		$this->object->setContentLength(111222);

		$this->assertHeaderWasSet('Content-Length', 111222);
	}

	public function testSetContentLanguage()
	{
		$this->object->setContentLanguage('en');

		$this->assertHeaderWasSet('Content-Language', 'en');
	}

	public function testSetIsDownload()
	{
		$this->object->setIsDownload();

		$this->assertHeaderWasSet('Content-Disposition', 'attachment');
	}

	public function testSetCacheabilityToNone()
	{
		$this->object->setCacheability(0);

		$this->assertHeaderWasSet('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
		$this->assertHeaderWasSet('Cache-Control', 'private, no-store, no-cache, must-revalidate');
	}

	public function testSetPrivateCacheability()
	{
		$this->object->setCacheability(600, false);

		$this->assertHeaderWasSet('Expires', $this->unixToHttpTimestamp(self::CURRENT_TIMESTAMP + 600));
		$this->assertHeaderWasSet('Cache-Control', 'private, max-age=600');
	}

	public function testSetPublicCacheability()
	{
		$this->object->setCacheability(123, true);

		$this->assertHeaderWasSet('Expires', $this->unixToHttpTimestamp(self::CURRENT_TIMESTAMP + 123));
		$this->assertHeaderWasSet('Cache-Control', 'public, max-age=123');
	}

	public function testSetLastModified()
	{
		$this->object->setLastModified(2000000000);

		$this->assertHeaderWasSet('Last-Modified', $this->unixToHttpTimestamp(2000000000));
	}

	public function testSetETag()
	{
		$this->object->setETag('XYZ123');

		$this->assertHeaderWasSet('Etag', 'XYZ123');
	}

	public function testSetAllowedCrossSiteXhrOrigin()
	{
		$this->object->setAllowedCrossSiteXhrOrigin('https://source.com');

		$this->assertHeaderWasSet('Access-Control-Allow-Origin', 'https://source.com');
	}

	/**
	 * @param int $unixTimestamp
	 * @return string
	 */
	private function unixToHttpTimestamp( $unixTimestamp )
	{
		return \gmdate('D, d M Y H:i:s', $unixTimestamp) . ' GMT';
	}

	private function assertHeaderWasSet( $key, $value )
	{
		Phake::verify($this->headerSenderMock)->setHeader($key, $value);
	}
}

