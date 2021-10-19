<?php
namespace XAF\file;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;;

/**
 * @covers \XAF\file\RegexFileFilter
 */
class RegexFileFilterTest extends TestCase
{
	public function testFoldersDoPass()
	{
		vfsStream::setup('work');
		$folder = vfsStream::url('work');

		$object = new RegexFileFilter('');

		$this->assertTrue($object->doesPass($folder));
	}

	public static function regexFileMatchDataProvider()
	{
		return [
			['foo.mp3', '', false], // empty pattern does not match
			['foo.mp3', '/bar/', false], // mismatch

			['foo.mp3', '/.*/', true], // matches all files

			['foo/bar.mp3', '/.*foo.*/', true], // matches filename
			['bar/foo.mp3', '/.*foo.*/', true], // matches foldername
		];
	}

	/**
	 * @dataProvider regexFileMatchDataProvider
	 */
	public function testFilePatterns( $file, $pattern, $willMatch )
	{
		vfsStream::setup('work');
		$folder = vfsStream::url('work');
		$mp3File = $folder . '/' . $file;
		$path = \str_replace(\basename($mp3File), '', $mp3File);
		if( !\file_exists($path) )
		{
			\mkdir(\str_replace(\basename($mp3File), '', $mp3File));
		}
		\file_put_contents($mp3File, '');

		$object = new RegexFileFilter($pattern);

		if( $willMatch )
		{
			$this->assertTrue($object->doesPass($mp3File));
		}
		else
		{
			$this->assertFalse($object->doesPass($mp3File));
		}
	}
}
