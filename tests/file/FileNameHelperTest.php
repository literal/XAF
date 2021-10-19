<?php
namespace XAF\file;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\file\FileNameHelper
 */
class FileNameHelperTest extends TestCase
{
	public function testExtractNameReturnsLastPathElement()
	{
		$this->assertEquals('bar', FileNameHelper::extractName('bar'));
		$this->assertEquals('bar', FileNameHelper::extractName('/bar'));
		$this->assertEquals('bar', FileNameHelper::extractName('../bar'));
		$this->assertEquals('.', FileNameHelper::extractName('bar/.'));
		$this->assertEquals('bar', FileNameHelper::extractName('/foo/bar'));
		$this->assertEquals('bar', FileNameHelper::extractName('\\foo\\bar'));
		$this->assertEquals('foo.bar', FileNameHelper::extractName('/foo/bar/foo.bar'));
	}

	public function testExtractNameIgnoresTrailingSlash()
	{
		$this->assertEquals('bar', FileNameHelper::extractName('/foo/bar/'));
		$this->assertEquals('bar', FileNameHelper::extractName('\\foo\\bar\\'));
	}

	public function testExtractNameUtf8LeavesUtf8SequencesUnchanged()
	{
		$this->assertEquals('bär', FileNameHelper::extractName('/foo/bär'));
		$this->assertEquals('bär', FileNameHelper::extractName('\\foo\\bär'));
	}

	public function testExtractNameUtf8CovertsLatinCharsToUtf8()
	{
		$result = FileNameHelper::extractNameUtf8(\utf8_decode('/foo/bär'));

		$this->assertEquals('bär', $result);
	}

	public function testExtractNameUtf8IgnoresTrailingSlash()
	{
		$this->assertEquals('bar', FileNameHelper::extractNameUtf8('/foo/bar/'));
		$this->assertEquals('bar', FileNameHelper::extractNameUtf8('\\foo\\bar\\'));
	}

	public function testExtractNameWithoutExtensionOnlyRemovesLastExtension()
	{
		$result = FileNameHelper::extractBasename('/foo/my.cool.document.doc');

		$this->assertEquals('my.cool.document', $result);
	}

	public function testExtractBasenameReturnsFileNameIfThereIsNoExtension()
	{
		$result = FileNameHelper::extractBasename('/foo.baz/bar');

		$this->assertEquals('bar', $result);
	}

	public function testExtractExtensionReturnsPartAfterLastDot()
	{
		$result = FileNameHelper::extractExtension('foo.bar.boom');

		$this->assertEquals('boom', $result);
	}

	public function testExtractExtensionReturnsEmptyStringIfThereIsNoExtension()
	{
		$result = FileNameHelper::extractExtension('/foo.bar/boom');

		$this->assertEquals('', $result);
	}

	public function testExtractDirectoryPathFromDirectory()
	{
		$result = FileNameHelper::extractDirectoryPath('/foo/bar/');

		$this->assertEquals('/foo', $result);
	}

	public function testExtractDirectoryPath()
	{
		$result = FileNameHelper::extractDirectoryPath('/foo/bar/foo.bar');

		$this->assertEquals('/foo/bar', $result);
	}

	public function testExtractRelativeDirectoryPath()
	{
		$result = FileNameHelper::extractDirectoryPath('foo/foo.bar');

		$this->assertEquals('foo', $result);
	}

	public function testExtractEmptyDirectoryPath()
	{
		$result = FileNameHelper::extractDirectoryPath('foo.bar');

		$this->assertSame('', $result);
	}

	public function testNormalizePath()
	{
		$this->assertEquals('/foo', FileNameHelper::normalizePath('/foo'));
		$this->assertEquals('/foo', FileNameHelper::normalizePath('/foo/'));
		$this->assertEquals('/foo', FileNameHelper::normalizePath('\\foo'));
		$this->assertEquals('/foo', FileNameHelper::normalizePath('\\foo\\'));
	}

	public function testCanonicalizePathResolvesBackrefs()
	{
		$result = FileNameHelper::canonicalizePath('/foo/bom/baz/../../bar');

		$this->assertEquals('/foo/bar', $result);
	}

	public function testCanonicalizePathResolvesTrailingBackref()
	{
		$result = FileNameHelper::canonicalizePath('/foo/bar/..');

		$this->assertEquals('/foo', $result);
	}

	public function testCanonicalizePathRemovesTrailingSlash()
	{
		$result = FileNameHelper::canonicalizePath('/foo/bar/');

		$this->assertEquals('/foo/bar', $result);
	}

	public function testCanonicalizePathIgnoresDotComponents()
	{
		$result = FileNameHelper::canonicalizePath('/foo/./bar/.');

		$this->assertEquals('/foo/bar', $result);
	}

	public function testCanonicalizePathIgnoresEmptyComponents()
	{
		$result = FileNameHelper::canonicalizePath('/foo//bar');

		$this->assertEquals('/foo/bar', $result);
	}

	public function testCanonicalizePathTreatsBackslashLikeSlash()
	{
		$result = FileNameHelper::canonicalizePath('\\foo\\bar\\');

		$this->assertEquals('/foo/bar', $result);
	}

	public function testCanonicalizePathLeavesRelativePathRelative()
	{
		$result = FileNameHelper::canonicalizePath('foo/bom/baz/../../bar');

		$this->assertEquals('foo/bar', $result);
	}

	public function testCanonicalizePathRemovesLeadingDotFromRelativePath()
	{
		$result = FileNameHelper::canonicalizePath('./foo/bar');

		$this->assertEquals('foo/bar', $result);
	}

	public function testCanonicalizePathIgnoresBackrefBelowAbsoluteRoot()
	{
		$result = FileNameHelper::canonicalizePath('/foo/../../bar');

		$this->assertEquals('/bar', $result);
	}

	public function testCanonicalizePathIgnoresBackrefBelowRelativeBase()
	{
		$result = FileNameHelper::canonicalizePath('foo/../../bar');

		$this->assertEquals('bar', $result);
	}

	public function testIsPathLocatedBelow()
	{
		// Given absolute path starts with base path
		$this->assertTrue(FileNameHelper::isPathLocatedBelow('/basepath/foo.mp3', '/basepath'));
		// Directory traversal is detected
		$this->assertFalse(FileNameHelper::isPathLocatedBelow('/base/sub/../foo.mp3', '/base/sub'));
		// Works with relative paths
		$this->assertTrue(FileNameHelper::isPathLocatedBelow('./folder/badsub/../goodsub/foo.mp3', 'folder/goodsub'));
	}
}
