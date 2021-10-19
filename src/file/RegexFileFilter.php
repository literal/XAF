<?php
namespace XAF\file;

class RegexFileFilter implements FileFilter
{
	/** @var string */
	private $pattern;

	public function __construct( $pattern )
	{
		$this->pattern = $pattern;
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
		if( $this->pattern === '' )
		{
			return false;
		}

		return \preg_match($this->pattern, $file) === 1;
	}
}
