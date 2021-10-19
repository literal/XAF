<?php
use XAF\test\FilesystemAssertMethods;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use XAF\test\DummyFileCreator;

use PHPUnit\Framework\ExpectationFailedException;

/**
 * @covers \XAF\test\FilesystemAssertMethods
 * @covers \XAF\test\FilesystemAssert
 * @covers \XAF\test\constraint\FolderContainsConstraint
 *
 * This is a special test. It tests the test helpers, i.e. the asserts.
 */
class FilesystemAssertMethodsTest extends TestCase
{
	use FilesystemAssertMethods;

	/** @var string */
	private $workPath;

	/** @var DummyFileCreator */
	private $fileCreator;

	public function setUp(): void
	{
		vfsStream::setup('work');
		$this->workPath = vfsStream::url('work');
		$this->fileCreator = new DummyFileCreator($this->workPath);
	}

	public function testAssertFolderContainsWithBooleanFileExistence()
	{
		$this->fileCreator->createFiles(['foo.ext', 'foo/bar.ext', 'foo/quux']);

		// It doesn't matter that another file "quux" also exists
		$this->assertFolderContains(['bar.ext' => true, 'nosuchfile' => false], $this->workPath . '/foo');
	}

	public function testAssertFolderContainsOnlyWithBooleanFileExistence()
	{
		$this->fileCreator->createFiles(['foo.ext', 'foo/bar.ext']);

		// Here the extra file would cause a failure
		$this->assertFolderContainsOnly(['bar.ext' => true, 'nosuchfile' => false], $this->workPath . '/foo');
	}

	static public function getFolderContainsOnlyPassingAssertTuples(): array
	{
		return [
			// Required file present
			[['foo.ext'], ['foo.ext' => true]],

			// Forbidden file not present
			[[], ['foo.ext' => false]],

			// Required files present and forbidden file not present in nested structure
			[
				['foo/foo.ext', 'bar/foo.ext'],
				['foo' => ['foo.ext' => true], 'bar' => ['foo.ext' => true, 'bar.ext' => false]]
			],
		];
	}

	/**
	 * @dataProvider getFolderContainsOnlyPassingAssertTuples
	 */
	public function testAssertFolderContainsOnlyPasses(array $filesToCreate, $assertPattern)
	{
		$this->fileCreator->createFiles($filesToCreate);

		$this->assertFolderContainsOnly($assertPattern, $this->workPath);
	}

	static public function getFolderContainsOnlyFailingAssertTuples(): array
	{
		return [
			// Required file not present
			[[], ['foo.ext' => true]],

			// Forbidden file present
			[['foo.ext'], ['foo.ext' => false]],

			// Extra file present
			[['foo.ext', 'bar.ext'], ['foo.ext' => true]],

			// Forbidden file present in nested structure
			[
				['foo/foo.ext', 'bar/foo.ext', 'bar/bar.ext'],
				['foo' => ['foo.ext' => true], 'bar' => ['foo.ext' => true, 'bar.ext' => false]]
			],
		];
	}

	/**
	 * @dataProvider getFolderContainsOnlyFailingAssertTuples
	 */
	public function testAssertFolderContainsOnlyFails(array $filesToCreate, $assertPattern)
	{
		$this->fileCreator->createFiles($filesToCreate);

		try
		{
			$this->assertFolderContainsOnly($assertPattern, $this->workPath);
		}
		catch( ExpectationFailedException $e )
		{
		}

		if( empty($e) )
		{
			$this->fail('Assertion did not fail as expected');
		}
	}
}
