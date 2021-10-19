<?php
namespace XAF\file;

class HiddenFileFilter implements FileFilter
{
	/**
	 * @param string $file absolute file path
	 * @return boolean
	 */
	public function doesPass( $file )
	{
		$fileName = FileNameHelper::extractName($file);
		return $fileName !== '' && $fileName[0] !== '.';
	}
}
