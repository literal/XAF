<?php
namespace XAF\file;

use XAF\file\FileNameHelper;

class ExtensionFileFilter implements FileFilter
{
	/** @var array */
	private $extensions;

	public function __construct( array $extensions )
	{
		$this->extensions = \array_map('strtolower', $extensions);
	}

	/**
	 * @param string $file absolute file path
	 * @return boolean
	 */
	public function doesPass( $file )
	{
		if( \is_dir($file) )
		{
			return true;
		}

		$fileExtension = \strtolower(FileNameHelper::extractExtension($file));
		return \in_array($fileExtension, $this->extensions);
	}
}
