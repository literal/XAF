<?php
namespace XAF\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\helper\MimeTypeResolver
 */
class MimeTypeResolverTest extends TestCase
{
	/**
	 * @var MimeTypeResolver
	 */
	protected $object;

	protected function setUp(): void
	{
		$mimeTypeToExtensionMap = [
			'audio/mpeg' => 'mp3',
			'video/mpeg' => 'mpeg',
			'foo/bar' => 'foo'
		];
		$extensionToMimeTypeMap = [
			'mp3' => 'audio/mpeg',
			'mpeg' => 'video/mpeg',
			'foo' => 'foo/bar'
		];
		$this->object = new MimeTypeResolver($mimeTypeToExtensionMap, $extensionToMimeTypeMap);
	}

	public function testGetMime()
	{
		$result = $this->object->getMimeTypeFromFileName('foo/bar.mp3');

		$this->assertEquals('audio/mpeg', $result);
	}

	public function testGetMimeIsCaseInsensitive()
	{
		$result = $this->object->getMimeTypeFromFileName('foo.FOO');

		$this->assertEquals('foo/bar', $result);
	}

	public function testGetMimeReturnsApplicationOctetUnknownExtension()
	{
		$result = $this->object->getMimeTypeFromFileName('foo.bar');

		$this->assertEquals('application/octet-stream', $result);
	}

	public function testGetExtensionReturnsFirstMatchingExtensionInMap()
	{
		$result = $this->object->getDefaultFileNameExtensionFromMimeType('audio/mpeg');

		$this->assertEquals('mp3', $result);
	}

	public function testGetExtensionIsCaseInsensitive()
	{
		$result = $this->object->getDefaultFileNameExtensionFromMimeType('foo/BAR');

		$this->assertEquals('foo', $result);
	}

	public function testGetExtensionReturnsNullByDefault()
	{
		$result = $this->object->getDefaultFileNameExtensionFromMimeType('foo/x-bar');

		$this->assertNull($result);
	}
}
