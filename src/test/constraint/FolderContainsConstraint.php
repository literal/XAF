<?php
namespace XAF\test\constraint;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;

class FolderContainsConstraint extends Constraint
{
	/** @var array */
	private $folderContentsMatchers;

	/** @var bool */
	private $mustFoldersContainNothingExcept;

	/**
	 * @param array $folderContentsMatchers
	 * @param bool $mustFoldersContainNothingExcept Whether any additional folder tree contents shall cause the
	 *	evaluation to fail
	 */
	public function __construct( array $folderContentsMatchers, $mustFoldersContainNothingExcept = false )
	{
		$this->folderContentsMatchers = $folderContentsMatchers;
		$this->mustFoldersContainNothingExcept = $mustFoldersContainNothingExcept;
	}

	public function evaluate( $folderPath, $description = '', $returnResult = false ): ?bool
	{
		$failureMessages = $this->evaluateFolderContents($folderPath, $this->folderContentsMatchers);
		if( $this->mustFoldersContainNothingExcept )
		{
			$failureMessages = \array_merge(
				$failureMessages,
				$this->evaluateFolderContainsNothingExcept($folderPath, $this->folderContentsMatchers)
			);
		}

		if( $returnResult )
		{
			return !$failureMessages;
		}

		if( $failureMessages )
		{
			throw new ExpectationFailedException(
				($description ? $description . "\n" : '')
				. 'Failed asserting that folder "' . $folderPath . '" has expected contents:'
				. "\n-> " . \implode("\n-> ", $failureMessages)
			);
		}

		return null;
	}

	/**
	 * @param string $path
	 * @return array Failure messages. Match successful if the result is an empty array
	 */
	private function evaluateFolderContents( $path, array $expectedContents )
	{
		if( !\is_dir($path) )
		{
			return ['Failed asserting that folder exists: ' . $path];
		}

		$result = [];
		foreach( $expectedContents as $entryName => $constraint )
		{
			$entryPath = $path . '/' . $entryName;
			switch( true )
			{
				case \is_array($constraint):
					$result = \array_merge($result, $this->evaluateFolderContents($entryPath, $constraint));
					break;

				case false === $constraint:
					if( \file_exists($entryPath) )
					{
						$result[] = 'Failed asserting that file does not exist: ' . $entryPath;
					}
					break;

				default:
					$result = \array_merge($result, $this->evaluateSingleFile($entryPath, $constraint));
			}
		}
		return $result;
	}

	/**
	 * @param string $path
	 * @param mixed $constraint
	 *   true: File must exist (regardless of contents)
	 *   string: File must have exactly these contents
	 *   Constraint: FIle contents must match constraint
	 *
	 * @return array List of failure messages, match ok when empty
	 */
	private function evaluateSingleFile( $path, $constraint )
	{
		// File existence is prerequisite for all checks of the file's contents
		if( !\file_exists($path) )
		{
			return ['Failed asserting that file exists: ' . $path];
		}

		if( $constraint === true )
		{
			return [];
		}

		$fileContents = \file_get_contents($path);

		if( $constraint instanceof Constraint )
		{
			if( !$constraint->evaluate($fileContents, '', true) )
			{
				return [
					'Failed asserting that contents of file "' . $path . '" ("' . $fileContents . '") '
					. $constraint->toString($fileContents)
				];
			}
		}
		else
		{
			if( $fileContents !== \strval($constraint) )
			{
				return [
					'Failed asserting that contents of file "' . $path . '" ("' . $fileContents . '") '
					. 'are equal to "' . \strval($constraint) . '".'
				];
			}
		}

		return [];
	}


	/**
	 * @param string $path
	 * @return array Failure messages. Match successful if the result is an empty array
	 */
	private function evaluateFolderContainsNothingExcept( $path, array $expectedContents )
	{
		if( !\is_dir($path) )
		{
			return [];
		}

		$result = [];
		foreach( \scandir($path) as $dirEntry )
		{
			if( \in_array($dirEntry, ['.', '..']) )
			{
				continue;
			}
			if( !isset($expectedContents[$dirEntry]) )
			{
				$result[] = 'Found unexpected file "' . $dirEntry . '" in folder "' . $path . '".';
			}
			else if( \is_array($expectedContents[$dirEntry]) )
			{
				$result = \array_merge(
					$result,
					$this->evaluateFolderContainsNothingExcept($path . '/' . $dirEntry, $expectedContents[$dirEntry])
				);
			}
		}

		return $result;
	}

	/**
	 * Returns a string representation of the constraint.
	 *
	 * @return string
	 */
	public function toString(): string
	{
		return 'folder has expected contents';
	}
}
