<?php
namespace XAF\file;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;;

/**
 * @covers \XAF\file\ExtensionFileFilter
 */
class ExtensionFileFilterTest extends TestCase
{
	/** @var ExtensionFileFilter */
	protected $object;

	/** @var array */
	private $expectedExtensions = ['mp3', 'wav'];

	protected function setUp(): void
	{
		$this->object = new ExtensionFileFilter($this->expectedExtensions);
	}

	public function testFoldersDoPass()
	{
		vfsStream::setup('work');
		$folder = vfsStream::url('work');

		$this->assertTrue($this->object->doesPass($folder));
	}

	public function testFilesWithExpectedExtensionsDoPass()
	{
		vfsStream::setup('work');
		$folder = vfsStream::url('work');
		$mp3File = $folder . '/foo.mp3';
		\file_put_contents($mp3File, '');

		$this->assertTrue($this->object->doesPass($mp3File));
	}

	public function testFilterIsCaseInsensitive()
	{
		vfsStream::setup('work');
		$folder = vfsStream::url('work');
		$mp3File = $folder . '/foo.MP3';
		\file_put_contents($mp3File, '');

		$this->assertTrue($this->object->doesPass($mp3File));
	}

	public function testFilesWithUnexpectedExtensionsDoNotPass()
	{
		vfsStream::setup('work');
		$folder = vfsStream::url('work');
		$oggFile = $folder . '/foo.ogg';
		\file_put_contents($oggFile, '');

		$this->assertFalse($this->object->doesPass($oggFile));
	}

	public function testEmptyFileNameDoesNotPass()
	{
		$this->assertFalse($this->object->doesPass(''));
	}
}
