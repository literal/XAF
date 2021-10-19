<?php
namespace XAF\file;

class DirectoryFileFilter implements FileFilter
{
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
		return false;
	}
}
