<?php
namespace XAF\test;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Assert;

/**
 * Helper Trait for using in PHPUnit TestCase classes.
 *
 * Adds assert methods to perform assertions on complex filesystem contents including arbitrary levels
 * of nested subsolders and assertions on file contents.
 *
 * Common format for expected folder/directory tree contents:
 *
 *   {<file/folder name>: <expected contents>, ...}
 *
 *   Where expected contents may be:
 *
 *   - array: Subfolder, {<file/folder name>: <expected contents>, ...}
 *   - string: exact contents of the file
 *   - PHPUnit Constraint to apply to contents of file
 *   - boolean true: File or folder must exist (regardless of contents)
 *   - boolean false: File or folder must not exist
 */
trait FilesystemAssertMethods
{
	/**
	 * Assert the given folder and its subfolders have only the expected contents and no other
	 * files or directories beyond that.
	 *
	 * @param array $expectedContents See class comment for expectation format
	 * @param string $folder Absolute path to folder
	 */
	protected function assertFolderContainsOnly( array $expectedContents, $folder )
	{
		FilesystemAssert::assertFolderContainsOnly($expectedContents, $folder);
	}

	/**
	 * Assert the given folder and its subfolders have the expected contents. The existence of other
	 * files or folders beyond the expected ones is tolerated.
	 *
	 * @param array $expectedContents See class comment for expectation format
	 * @param string $folder Absolute path to folder
	 */
	protected function assertFolderContains( array $expectedContents, $folder )
	{
		FilesystemAssert::assertFolderContains($expectedContents, $folder);
	}
}
