<?php
namespace XAF\test;

// This is part of PHPUnit
use SebastianBergmann\FileIterator\Facade as FileIterator;

/**
 * Static helper for simple test suites running all the tests below a particular path.
 *
 * This is about IDE integration. As of version 7.1.2 Netbeans cannot call PHPUnit for a directory of choice
 * or a suite defined in PHPUnit's XML configuration file. But Netbeans can run a custom suite class
 * (right-click and "run").
 */
class PhpUnitTestCollector
{
	/**
	 * @param string|array $paths
	 * @return array
	 */
	static public function collectTestFiles( $paths )
	{
		$fileIterator = new FileIterator();
		return $fileIterator->getFilesAsArray($paths, ['Test.php', '.phpt']);
	}
}
