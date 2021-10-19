<?php
namespace XAF\file;

class NullFileFilter implements FileFilter
{
	public function doesPass( $file )
	{
		return true;
	}
}
