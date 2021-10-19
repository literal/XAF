<?php
namespace XAF\file;

class FileDirEntry extends DirEntry
{
	/**
	 * @var int The file's size in bytes
	 */
	public $sizeBytes;

	/**
	 * @param string $name
	 * @param string $path
	 * @param int $lastModifiedTs
	 * @param int $sizeBytes
	 */
	public function __construct( $name, $path, $lastModifiedTs, $sizeBytes )
	{
		$this->sizeBytes = $sizeBytes;
		$this->type = 'file';
		parent::__construct($name, $path, $lastModifiedTs);
	}

}
