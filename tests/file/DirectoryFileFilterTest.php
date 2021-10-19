<?php
namespace XAF\file;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;;

/**
 * @covers \XAF\file\DirectoryFileFilter
 */
class DirectoryFileFilterTest extends TestCase
{
	public function testFoldersDoPass()
	{
		vfsStream::setup('work');
		$folder = vfsStream::url('work');

		$object = new DirectoryFileFilter();

		$this->assertTrue($object->doesPass($folder));
	}

	public function testFilesDoNotPass()
	{
		vfsStream::setup('work');
		$folder = vfsStream::url('work');
		$file = $folder . '/foo.txt';

		$object = new DirectoryFileFilter();

		$this->assertFalse($object->doesPass($file));
	}
}
