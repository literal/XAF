<?php
namespace XAF\file;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\file\HiddenFileFilter
 */
class HiddenFileFilterTest extends TestCase
{
	/** @var HiddenFileFilter */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new HiddenFileFilter;
	}

	public function testFilesAndFoldersWithPrecedingDotDoNotPass()
	{
		$this->assertFalse($this->object->doesPass('.svn'));
		$this->assertFalse($this->object->doesPass('foo/.foo'));
	}

	public function testFilesAndFoldersContainingDotPass()
	{
		$this->assertTrue($this->object->doesPass('foo.bar'));
	}

	public function testFilterOnlyLastPartOfThePath()
	{
		$this->assertTrue($this->object->doesPass('/.foo/bar'));
		$this->assertFalse($this->object->doesPass('/foo/.bar'));
	}

	public function testEmptyFileNameDoesNotPass()
	{
		$this->assertFalse($this->object->doesPass(''));
	}
}
