<?php
namespace XAF\file;

class FileNameHelper
{
	/**
	 * Extract file name from full file path
	 *
	 * @param string $file
	 * @return string
	 */
	static public function extractName( $file )
	{
		return \basename(self::normalizePath($file));
	}

	/**
	 * Extract file name from full file path for display purrposes and similar, forcing the name into UTF-8 encoding.
	 *
	 * @param string $file
	 * @return string
	 */
	static public function extractNameUtf8( $file )
	{
		return self::forceToUtf8(self::extractName($file));
	}

	/**
	 * @param string $string
	 * @return string
	 */
	static private function forceToUtf8( $string )
	{
		return \mb_check_encoding($string, 'UTF-8') ? $string : \utf8_encode($string);
	}

	/**
	 * Extract file name without extension from full file path
	 *
	 * @param string $file
	 * @return string
	 */
	static public function extractBasename( $file )
	{
		$fileName = self::extractName($file);
		$lastDotIndex = \strrpos($fileName, '.');
		return $lastDotIndex !== false ? \substr($fileName, 0, $lastDotIndex) : $fileName;
	}

	/**
	 * Extract extension after last dot from file name or full file path
	 *
	 * @param string $file
	 * @return string empty string if no extension
	 */
	static public function extractExtension( $file )
	{
		$fileName = self::extractName($file);
		$extensionWithDot = \strrchr($fileName, '.');
		return $extensionWithDot === false ? '' : \substr($extensionWithDot, 1);
	}

	/**
	 * Extract full directory path
	 *
	 * @param string $file
	 * @return string
	 */
	static public function extractDirectoryPath( $file )
	{
		$result = \dirname($file);
		return $result == '.' ? '' : $result;
	}

	/**
	 * Convert all separators to forward slashes and remove trailing directory separator char from directory name
	 *
	 * @param string $path
	 * @return string
	 */
	static public function normalizePath( $path )
	{
		$path = \str_replace('\\', '/', $path);
		return \rtrim($path, '/');
	}

	/**
	 * @param string $path
	 * @return string
	 */
	static public function canonicalizePath( $path )
	{
		$path = \str_replace('\\', '/', $path);
		$pathBeginsWithSlash = \strlen($path) > 0 && $path[0] == '/';
		$path = \trim($path, '/');
		$pathComponents = \explode('/', $path);
		$result = [];
		foreach( $pathComponents as $component )
		{
			if( $component == '..' )
			{
				\array_pop($result);
			}
			else if( $component !== '' && $component != '.' )
			{
				$result[] = $component;
			}
		}
		return ($pathBeginsWithSlash ? '/' : '') . \implode('/', $result);
	}

	/**
	 * @param string $path
	 * @param string $basePath
	 * @return string
	 */
	static public function isPathLocatedBelow( $path, $basePath )
	{
		$canonicalPath = self::canonicalizePath($path);
		$canonicalBasePath = self::canonicalizePath($basePath);
		return $canonicalPath && $canonicalBasePath && \strpos($canonicalPath, $canonicalBasePath) === 0;
	}
}
