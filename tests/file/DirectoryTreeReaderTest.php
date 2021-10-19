<?php
namespace XAF\file;

use PHPUnit\Framework\TestCase;
use Phake;
use XAF\test\DummyFileCreator;
use org\bovigo\vfs\vfsStream;

/**
 * @covers \XAF\file\DirectoryTreeReader
 */
class DirectoryTreeReaderTest extends TestCase
{
	/** @var string */
	private $vfsWorkPath;

	/** @var string */
	private $realWorkPath;

	/** @var DummyFileCreator */
	private $vfsFileCreator;

	/** @var DummyFileCreator */
	private $realFileCreator;

	/** @var FileFilter */
	private $fileFilterMock;

	/** @var DirectoryTreeReader */
	private $object;

	public function setUp(): void
	{
		$this->vfsWorkPath = $this->getWorkPath();
		$this->vfsFileCreator = new DummyFileCreator($this->vfsWorkPath);

		$this->realWorkPath = \WORK_PATH . '/dummyfiles';
		$this->realFileCreator = new DummyFileCreator($this->realWorkPath);
		$this->realFileCreator->emptyRootPath();

		$this->fileFilterMock = Phake::mock(FileFilter::class);
		$this->object = new DirectoryTreeReader($this->fileFilterMock);
	}

	private function getWorkPath()
	{
		if( $this->vfsWorkPath != null )
		{
			return $this->vfsWorkPath;
		}
		vfsStream::setup('work');
		return vfsStream::url('work');
	}

	public function tearDown(): void
	{
		$this->realFileCreator->emptyRootPath();
	}

	public function testGetTreeReturnsArrayOfDirEntryObjects()
	{
		$this->vfsFileCreator->createFile('foo.bar');
		$this->setAllFilesPassFilter();

		$resultAbs = $this->object->getTreeAbsolute($this->vfsWorkPath);
		$resultRel = $this->object->getTreeRelative($this->vfsWorkPath);
		$resultRelTo = $this->object->getTreeRelativeTo($this->vfsWorkPath, '');

		$this->assertInstanceOf('XAF\\file\\DirEntry', $resultAbs[0]);
		$this->assertInstanceOf('XAF\\file\\DirEntry', $resultRel[0]);
		$this->assertInstanceOf('XAF\\file\\DirEntry', $resultRelTo[0]);
	}

	public function testGetFlatReturnsArrayOfStrings()
	{
		$this->vfsFileCreator->createFile('foo.bar');
		$this->setAllFilesPassFilter();

		$resultAbs = $this->object->getFlatAbsolute($this->vfsWorkPath);
		$resultRel = $this->object->getFlatRelative($this->vfsWorkPath);
		$resultRelTo = $this->object->getFlatRelativeTo($this->vfsWorkPath, '');

		$this->assertIsString($resultAbs[0]);
		$this->assertIsString($resultRel[0]);
		$this->assertIsString($resultRelTo[0]);
	}

	public function testGetTreeContainsFoldersAndFiles()
	{
		$this->vfsFileCreator->createFiles(['foo.mp3', 'foo/bar.mp3']);
		$this->vfsFileCreator->createFolder('bar');
		$this->setAllFilesPassFilter();

		$resultAbs = $this->object->getTreeAbsolute($this->vfsWorkPath);
		$resultRel = $this->object->getTreeRelative($this->vfsWorkPath);
		$resultRelTo = $this->object->getTreeRelativeTo($this->vfsWorkPath, '');

		$this->assertCount(3, $resultAbs);
		$this->assertCount(3, $resultRel);
		$this->assertCount(3, $resultRelTo);
		$this->assertEquals('bar', $resultAbs[0]->name);
		$this->assertEquals('bar', $resultRel[0]->name);
		$this->assertEquals('bar', $resultRelTo[0]->name);
		$this->assertEquals('foo.mp3', $resultAbs[2]->name);
		$this->assertEquals('foo.mp3', $resultRel[2]->name);
		$this->assertEquals('foo.mp3', $resultRelTo[2]->name);
	}

	public function testGetFlatOnlyContainsFiles()
	{
		$this->vfsFileCreator->createFiles(['foo.mp3', 'foo/bar.mp3']);
		$this->vfsFileCreator->createFolder('bar');
		$this->setAllFilesPassFilter();

		$resultAbs = $this->object->getFlatAbsolute($this->vfsWorkPath);
		$resultRel = $this->object->getFlatRelative($this->vfsWorkPath);
		$resultRelTo = $this->object->getFlatRelativeTo($this->vfsWorkPath, '');

		$this->assertEquals([$this->vfsWorkPath . '/foo/bar.mp3', $this->vfsWorkPath . '/foo.mp3'], $resultAbs);
		$this->assertEquals(['foo/bar.mp3', 'foo.mp3'], $resultRel);
		$this->assertEquals(['foo/bar.mp3', 'foo.mp3'], $resultRelTo);
	}

	public function testGetTreeStopsIfMaxNestingLevelIsReached()
	{
		$this->vfsFileCreator->createFile('foo/bar/bom/foo.bar');
		$this->setAllFilesPassFilter();

		$resultAbs = $this->object->getTreeAbsolute($this->vfsWorkPath . '/foo', ['maxDepth' => 2]);
		$resultRel = $this->object->getTreeRelative($this->vfsWorkPath . '/foo', ['maxDepth' => 2]);
		$resultRelTo = $this->object->getTreeRelativeTo($this->vfsWorkPath, 'foo', ['maxDepth' => 2]);

		$this->assertEmpty($resultAbs[0]->contents);
		$this->assertEmpty($resultRel[0]->contents);
		$this->assertEmpty($resultRelTo[0]->contents);
	}

	public function testGetFlatStopsIfMaxNestingLevelIsReached()
	{
		$this->vfsFileCreator->createFile('foo/bar/bom/foo.bar');
		$this->setAllFilesPassFilter();

		$resultAbs = $this->object->getFlatAbsolute($this->vfsWorkPath . '/foo', ['maxDepth' => 2]);
		$resultRel = $this->object->getFlatRelative($this->vfsWorkPath . '/foo', ['maxDepth' => 2]);
		$resultRelTo = $this->object->getFlatRelativeTo($this->vfsWorkPath, 'foo', ['maxDepth' => 2]);

		$this->assertEmpty($resultAbs);
		$this->assertEmpty($resultRel);
		$this->assertEmpty($resultRelTo);
	}

	public function testGetTreeNestingLevelZeroReturnsNothing()
	{
		$this->vfsFileCreator->createFiles(['foo.mp3', 'foo/bar.mp3']);
		$this->vfsFileCreator->createFolder('bar');
		$this->setAllFilesPassFilter();

		$resultAbs = $this->object->getTreeAbsolute($this->vfsWorkPath, ['maxDepth' => 0]);
		$resultRel = $this->object->getTreeRelative($this->vfsWorkPath, ['maxDepth' => 0]);
		$resultRelTo = $this->object->getTreeRelativeTo($this->vfsWorkPath, '', ['maxDepth' => 0]);

		$this->assertEquals([], $resultAbs);
		$this->assertEquals([], $resultRel);
		$this->assertEquals([], $resultRelTo);
	}

	public function testGetFlatNestingLevelZeroReturnsNothing()
	{
		$this->vfsFileCreator->createFiles(['foo.mp3', 'foo/bar.mp3']);
		$this->vfsFileCreator->createFolder('bar');
		$this->setAllFilesPassFilter();

		$resultAbs = $this->object->getFlatAbsolute($this->vfsWorkPath, ['maxDepth' => 0]);
		$resultRel = $this->object->getFlatRelative($this->vfsWorkPath, ['maxDepth' => 0]);
		$resultRelTo = $this->object->getFlatRelativeTo($this->vfsWorkPath, '', ['maxDepth' => 0]);

		$this->assertEquals([], $resultAbs);
		$this->assertEquals([], $resultRel);
		$this->assertEquals([], $resultRelTo);
	}

	public function testGetTreeNestingLevelOneReturnsContainedFilesAndFolders()
	{
		$this->vfsFileCreator->createFiles(['foo.mp3', 'foo/bar.mp3']);
		$this->setAllFilesPassFilter();

		$resultAbs = $this->object->getTreeAbsolute($this->vfsWorkPath, ['maxDepth' => 1]);
		$resultRel = $this->object->getTreeRelative($this->vfsWorkPath, ['maxDepth' => 1]);
		$resultRelTo = $this->object->getTreeRelativeTo($this->vfsWorkPath, '', ['maxDepth' => 1]);

		$this->assertEquals('vfs://work/foo', $resultAbs[0]->path);
		$this->assertEquals([], $resultAbs[0]->contents);
		$this->assertEquals('vfs://work/foo.mp3', $resultAbs[1]->path);
		$this->assertEquals('foo', $resultRel[0]->path);
		$this->assertEquals([], $resultRel[0]->contents);
		$this->assertEquals('foo.mp3', $resultRel[1]->path);
		$this->assertEquals('foo', $resultRelTo[0]->path);
		$this->assertEquals([], $resultRel[0]->contents);
		$this->assertEquals('foo.mp3', $resultRelTo[1]->path);
	}

	public function testGetFlatNestingLevelOneReturnsContainedFilesOnly()
	{
		$this->vfsFileCreator->createFiles(['foo.mp3', 'foo/bar.mp3']);
		$this->setAllFilesPassFilter();

		$resultAbs = $this->object->getFlatAbsolute($this->vfsWorkPath, ['maxDepth' => 1]);
		$resultRel = $this->object->getFlatRelative($this->vfsWorkPath, ['maxDepth' => 1]);
		$resultRelTo = $this->object->getFlatRelativeTo($this->vfsWorkPath, '', ['maxDepth' => 1]);

		$this->assertEquals([$this->vfsWorkPath . '/foo.mp3'], $resultAbs);
		$this->assertEquals(['foo.mp3'], $resultRel);
		$this->assertEquals(['foo.mp3'], $resultRelTo);
	}

	public function testGetTreeFiltersByFileFilter()
	{
		$this->vfsFileCreator->createFiles(['foo/foo.mp3', 'bar/bar.jpg']);
		$this->setAllFilesPassFilter();
		$fileFilterMock = Phake::mock(FileFilter::class);  /* @var $fileFilterMock FileFilter */
		Phake::when($fileFilterMock)->doesPass(Phake::anyParameters())->thenReturnCallback(
			function( $value ) { return \is_dir($value) || $value === 'vfs://work/foo/foo.mp3'; }
		);

		$resultAbs = $this->object->getTreeAbsolute($this->vfsWorkPath, ['fileFilter' => $fileFilterMock]);
		$resultRel = $this->object->getTreeRelative($this->vfsWorkPath, ['fileFilter' => $fileFilterMock]);
		$resultRelTo = $this->object->getTreeRelativeTo($this->vfsWorkPath, '', ['fileFilter' => $fileFilterMock]);

		$this->assertEmpty($resultAbs[0]->contents);
		$this->assertEmpty($resultRel[0]->contents);
		$this->assertEmpty($resultRelTo[0]->contents);
		$this->assertEquals($this->vfsWorkPath . '/foo/foo.mp3', $resultAbs[1]->contents[0]->path);
		$this->assertEquals('foo/foo.mp3', $resultRel[1]->contents[0]->path);
		$this->assertEquals('foo/foo.mp3', $resultRelTo[1]->contents[0]->path);
	}

	public function testGetFlatFiltersByFileFilter()
	{
		$this->vfsFileCreator->createFiles(['foo/foo.mp3', 'bar/bar.jpg']);
		$this->setAllFilesPassFilter();
		$fileFilterMock = Phake::mock(FileFilter::class);  /* @var $fileFilterMock FileFilter */
		Phake::when($fileFilterMock)->doesPass(Phake::anyParameters())->thenReturnCallback(
			function( $value ) { return \is_dir($value) || $value === 'vfs://work/foo/foo.mp3'; }
		);

		$resultAbs = $this->object->getFlatAbsolute($this->vfsWorkPath, ['fileFilter' => $fileFilterMock]);
		$resultRel = $this->object->getFlatRelative($this->vfsWorkPath, ['fileFilter' => $fileFilterMock]);
		$resultRelTo = $this->object->getFlatRelativeTo($this->vfsWorkPath, '', ['fileFilter' => $fileFilterMock]);

		$this->assertCount(1, $resultAbs);
		$this->assertCount(1, $resultRel);
		$this->assertCount(1, $resultRelTo);
		$this->assertEquals([$this->vfsWorkPath . '/foo/foo.mp3'], $resultAbs);
		$this->assertEquals(['foo/foo.mp3'], $resultRel);
		$this->assertEquals(['foo/foo.mp3'], $resultRelTo);
	}

	public function testGetTreeEncodesFilenameWhilePathIsLeftAsIs()
	{
		$utf8EncodedFileName = 'lätin.mp3';
		$latinEncodedFileName = \utf8_decode($utf8EncodedFileName);
		$this->vfsFileCreator->createFile($latinEncodedFileName);
		$this->setAllFilesPassFilter();

		$resultAbs = $this->object->getTreeAbsolute($this->vfsWorkPath);
		$resultRel = $this->object->getTreeRelative($this->vfsWorkPath);
		$resultRelTo = $this->object->getTreeRelativeTo($this->vfsWorkPath, '');

		$this->assertEquals($utf8EncodedFileName, $resultAbs[0]->name);
		$this->assertEquals($this->vfsWorkPath . '/' . $latinEncodedFileName, $resultAbs[0]->path);
		$this->assertEquals($utf8EncodedFileName, $resultRel[0]->name);
		$this->assertEquals($latinEncodedFileName, $resultRel[0]->path);
		$this->assertEquals($utf8EncodedFileName, $resultRelTo[0]->name);
		$this->assertEquals($latinEncodedFileName, $resultRelTo[0]->path);
	}

	public function testGetTreeContainsFileSizeAndModificationTime()
	{
		$this->vfsFileCreator->createFile('foo.mp3', 'xyz');
		$this->setAllFilesPassFilter();
		$time = \filemtime($this->vfsWorkPath . '/foo.mp3');

		$resultAbs = $this->object->getTreeAbsolute($this->vfsWorkPath);
		$resultRel = $this->object->getTreeRelative($this->vfsWorkPath);
		$resultRelTo = $this->object->getTreeRelativeTo($this->vfsWorkPath, '');

		$this->assertEquals(3, $resultAbs[0]->sizeBytes);
		$this->assertEquals(3, $resultRel[0]->sizeBytes);
		$this->assertEquals(3, $resultRelTo[0]->sizeBytes);
		$this->assertEquals($time, $resultAbs[0]->lastModifiedTs);
		$this->assertEquals($time, $resultRel[0]->lastModifiedTs);
		$this->assertEquals($time, $resultRelTo[0]->lastModifiedTs);
	}

	public function testGetTreeSortsResultByName()
	{
		$this->vfsFileCreator->createFiles(['zib/foo.mp3', 'foo.mp3', 'abc/bar.mp3', 'abc/foo.mp3']);
		$this->vfsFileCreator->createFolder('bar');
		$this->setAllFilesPassFilter();

		$resultAbs = $this->object->getTreeAbsolute($this->vfsWorkPath);
		$resultRel = $this->object->getTreeRelative($this->vfsWorkPath);
		$resultRelTo = $this->object->getTreeRelativeTo($this->vfsWorkPath, '');

		$this->assertDirEntriesAreSortedByName($resultAbs);
		$this->assertDirEntriesAreSortedByName($resultRel);
		$this->assertDirEntriesAreSortedByName($resultRelTo);
	}

	/**
	 * @param DirEntry[] $dirEntries
	 */
	private function assertDirEntriesAreSortedByName( array $dirEntries )
	{
		$lastEntryName = null;
		foreach( $dirEntries as $dirEntry ) /* @var $dirEntry DirEntry */
		{
			if( isset($lastEntryName) )
			{
				$this->assertGreaterThan($lastEntryName, $dirEntry->name);
			}
			if( $dirEntry instanceof FolderDirEntry )
			{
				$this->assertDirEntriesAreSortedByName($dirEntry->contents);
			}
			$lastEntryName = $dirEntry->name;
		}
	}

	public function testGetFlatSortsResult()
	{
		$this->vfsFileCreator->createFiles(['zib/foo.mp3', 'foo.mp3', 'abc/bar.mp3', 'abc/foo.mp3']);
		$this->setAllFilesPassFilter();

		$resultAbs = $this->object->getFlatAbsolute($this->vfsWorkPath);
		$resultRel = $this->object->getFlatRelative($this->vfsWorkPath);
		$resultRelTo = $this->object->getFlatRelativeTo($this->vfsWorkPath, '');

		$this->assertEquals(
			[
				0 => $this->vfsWorkPath . '/abc/bar.mp3',
				1 => $this->vfsWorkPath . '/abc/foo.mp3',
				2 => $this->vfsWorkPath . '/foo.mp3',
				3 => $this->vfsWorkPath . '/zib/foo.mp3'
			],
			$resultAbs
		);
		$this->assertEquals(
			[
				0 => 'abc/bar.mp3',
				1 => 'abc/foo.mp3',
				2 => 'foo.mp3',
				3 => 'zib/foo.mp3'
			],
			$resultRel
		);
		$this->assertEquals(
			[
				0 => 'abc/bar.mp3',
				1 => 'abc/foo.mp3',
				2 => 'foo.mp3',
				3 => 'zib/foo.mp3'
			],
			$resultRelTo
		);
	}

	public function testGetTreeNormalizesSourcePath()
	{
		$this->vfsFileCreator->createFile('foo/foo.bar');
		$this->setAllFilesPassFilter();

		$path = 'foo\\'; // note the trailing backslash
		$resultAbs = $this->object->getTreeAbsolute($this->vfsWorkPath . '/' . $path);
		$resultRel = $this->object->getTreeRelative($this->vfsWorkPath . '/' . $path);
		$resultRelTo = $this->object->getTreeRelativeTo($this->vfsWorkPath, $path);

		$this->assertEquals($this->vfsWorkPath . '/foo/foo.bar', $resultAbs[0]->path);
		$this->assertEquals('foo.bar', $resultRel[0]->path);
		$this->assertEquals('foo/foo.bar', $resultRelTo[0]->path);
	}

	public function testGetFlatNormalizesSourcePath()
	{
		$this->vfsFileCreator->createFile('foo/foo.bar');
		$this->setAllFilesPassFilter();

		$path = 'foo\\'; // note the trailing backslash
		$resultAbs = $this->object->getFlatAbsolute($this->vfsWorkPath . '/' . $path);
		$resultRel = $this->object->getFlatRelative($this->vfsWorkPath . '/' . $path);
		$resultRelTo = $this->object->getFlatRelativeTo($this->vfsWorkPath, $path);

		$this->assertEquals($this->vfsWorkPath . '/foo/foo.bar', $resultAbs[0]);
		$this->assertEquals('foo.bar', $resultRel[0]);
		$this->assertEquals('foo/foo.bar', $resultRelTo[0]);
	}

	public function testGetTreeDefaultDepthIsTen()
	{
		$this->vfsFileCreator->createFile('1/2/3/4/5/6/7/8/9/10/foo');
		$this->vfsFileCreator->createFile('1/2/3/4/5/6/7/8/9/10/11/foo'); // too deep, should be ignored
		$this->setAllFilesPassFilter();

		$resultAbs = $this->object->getTreeAbsolute($this->vfsWorkPath);
		$resultRel = $this->object->getTreeRelative($this->vfsWorkPath);
		$resultRelTo = $this->object->getTreeRelativeTo($this->vfsWorkPath, '');

		$this->assertDeepestEntryNameEquals('10', $resultAbs);
		$this->assertDeepestEntryNameEquals('10', $resultRel);
		$this->assertDeepestEntryNameEquals('10', $resultRelTo);
	}

	/**
	 * @param string $expectedName
	 * @param array $entries
	 * @return array
	 */
	private function assertDeepestEntryNameEquals( $expectedName, array $entries )
	{
		while( isset($entries[0]) && !empty($entries[0]->contents) )
		{
			$entries = $entries[0]->contents;
		}
		$this->assertEquals($expectedName, $entries[0]->name);
	}

	public function testGetFlatDefaultDepthIsTen()
	{
		$this->vfsFileCreator->createFile('1/2/3/4/5/6/7/8/9/10/foo');
		$this->vfsFileCreator->createFile('1/2/3/4/5/6/7/8/9/10/11/foo'); // too deep, should be ignored
		$this->setAllFilesPassFilter();

		$resultAbs = $this->object->getFlatAbsolute($this->vfsWorkPath);
		$resultRel = $this->object->getFlatRelative($this->vfsWorkPath);
		$resultRelTo = $this->object->getFlatRelativeTo($this->vfsWorkPath, '');

		$this->assertEquals([$this->vfsWorkPath . '/1/2/3/4/5/6/7/8/9/10/foo'], $resultAbs);
		$this->assertEquals(['1/2/3/4/5/6/7/8/9/10/foo'], $resultRel);
		$this->assertEquals(['1/2/3/4/5/6/7/8/9/10/foo'], $resultRelTo);
	}

	public function testGetTreeAbsoluteForUnreadableFolderThrowsException()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->getTreeAbsolute($this->createUnreadableFolder());
	}

	public function testGetTreeRelativeForUnreadableFolderThrowsException()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->getTreeRelative($this->createUnreadableFolder());
	}

	public function testGetTreeRelativeToForUnreadableFolderThrowsException()
	{
		$unreadableFolder = $this->createUnreadableFolder();

		$this->expectException(\XAF\file\FileError::class);
		$this->object->getTreeRelativeTo(dirname($unreadableFolder), basename($unreadableFolder));
	}

	public function testGetFlatAbsoluteForUnreadableFolderThrowsException()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->getFlatAbsolute($this->createUnreadableFolder());
	}

	public function testGetFlatRelativeForUnreadableFolderThrowsException()
	{
		$this->expectException(\XAF\file\FileError::class);
		$this->object->getFlatRelative($this->createUnreadableFolder());
	}

	public function testGetFlatRelativeToForUnreadableFolderThrowsException()
	{
		$unreadableFolder = $this->createUnreadableFolder();

		$this->expectException(\XAF\file\FileError::class);
		$this->object->getFlatRelativeTo(dirname($unreadableFolder), basename($unreadableFolder));
	}

	/**
	 * @return string Full path
	 */
	private function createUnreadableFolder()
	{
		$unreadableFolder = $this->vfsWorkPath . '/unreadable';
		mkdir($unreadableFolder, 000);
		return $unreadableFolder;
	}

	private function setAllFilesPassFilter()
	{
		Phake::when($this->fileFilterMock)->doesPass(Phake::anyParameters())->thenReturn(true);
	}

	/**
	 * Test runs on REAL FILE SYSTEM because vfsStream does not return the usual "." and ".." entries
	 * when reading directories.
	 *
	 * This test would cause an infinite loop if dot directories ("." and "..") were not excluded
	 */
	public function testGetTreeFiltersDirectoryNames()
	{
		$this->realFileCreator->createFiles(['foo/foo.txt', '.bar/bar.txt']);
		$this->setFilesPassFilter(['foo', 'foo/foo.txt', 'bar.txt'], $this->realWorkPath);

		$resultAbs = $this->object->getTreeAbsolute($this->realWorkPath);
		$resultRel = $this->object->getTreeRelative($this->realWorkPath);
		$resultRelTo = $this->object->getTreeRelativeTo($this->realWorkPath, '');

		$this->assertCount(1, $resultAbs);
		$this->assertCount(1, $resultRel);
		$this->assertCount(1, $resultRelTo);
	}

	/**
	 * Test runs on REAL FILE SYSTEM because vfsStream does not return the usual "." and ".." entries
	 * when reading directories.
	 *
	 * This test would cause an infinite loop if dot directories ("." and "..") were not excluded
	 */
	public function testGetFlatFiltersDirectoryNames()
	{
		$this->realFileCreator->createFiles(['foo/foo.txt', '.bar/bar.txt']);
		$this->setFilesPassFilter(['foo', 'foo/foo.txt', 'bar.txt'], $this->realWorkPath);

		$resultAbs = $this->object->getFlatAbsolute($this->realWorkPath);
		$resultRel = $this->object->getFlatRelative($this->realWorkPath);
		$resultRelTo = $this->object->getFlatRelativeTo($this->realWorkPath, '');

		$this->assertCount(1, $resultAbs);
		$this->assertCount(1, $resultRel);
		$this->assertCount(1, $resultRelTo);
	}

	private function setFilesPassFilter( array $files, $rootPath = null )
	{
		$rootPath = $rootPath ?: $this->vfsWorkPath;
		Phake::when($this->fileFilterMock)->doesPass(Phake::anyParameters())->thenReturn(false);
		foreach( $files as $file )
		{
			$normalizedPath = \str_replace('\\', '/', $rootPath . '/' . $file);
			Phake::when($this->fileFilterMock)->doesPass($normalizedPath)->thenReturn(true);
		}
	}
}
