<?php
namespace XAF\test;

use PHPUnit\Framework\Assert;

/**
 * Extension of the PHPUnit Assert Class to evaluate complex filesystem contents expectations
 */
class FilesystemAssert
{
	/**
	 * @param array $expectedContents {<file/folder name>: <expected contents>, ...}
	 *
	 *   Where expected contents may be:
	 *   - array: Subfolder, Another nested level of {<file/folder name>: <expected contents>, ...}
	 *   - string: exact contents of the file
	 *   - PHPUnit Constraint to apply to contents of file
	 *   - boolean true: File or folder must exist (regardless of contents)
	 *   - boolean false: File or folder must not exist
	 *
	 * @param string $folderPath
	 */
	public static function assertFolderContains( array $expectedContents, $folderPath )
	{
		Assert::assertThat($folderPath, new constraint\FolderContainsConstraint($expectedContents));
	}

	/**
	 * @param array $expectedContents {<file/folder name>: <expected contents>, ...}
	 *
	 *   Where expected contents may be:
	 *   - array: Subfolder, Another nested level of {<file/folder name>: <expected contents>, ...}
	 *   - string: exact contents of the file
	 *   - PHPUnit Constraint to apply to contents of file
	 *   - boolean true: File or folder must exist (regardless of contents)
	 *   - boolean false: File or folder must not exist
	 *
	 * @param string $folderPath
	 */
	public static function assertFolderContainsOnly( array $expectedContents, $folderPath )
	{
		Assert::assertThat($folderPath, new constraint\FolderContainsConstraint($expectedContents, true));
	}
}
