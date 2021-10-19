<?php
namespace XAF\file;

class FolderDirEntry extends DirEntry
{
	/**
	 * @var array The contained DirEntry objects
	 */
	public $contents = [];

	/**
	 * @param string $name
	 * @param string $path
	 * @param int $lastModifiedTs
	 */
	public function __construct( $name, $path, $lastModifiedTs )
	{
		$this->type = 'folder';
		parent::__construct($name, $path, $lastModifiedTs);
	}
}
