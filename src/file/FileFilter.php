<?php
namespace XAF\file;

interface FileFilter
{
	/**
	 * Filter a given File return TRUE if file passes the filter
	 *
	 * @param string $file absolute file path
	 * @return boolean
	 */
	public function doesPass( $file );
}
