<?php
namespace XAF\file;

class DirEntry
{
	/**
	 * @var string file/folder Name
	 */
	public $name;

	/**
	 * @var string path to file or folder
	 */
	public $path;

	/**
	 * @var int
	 */
	public $lastModifiedTs;

	/**
	 * @var string either 'file' or 'folder'
	 */
	public $type;

	/**
	 * @param string $name
	 * @param string $path
	 * @param int $lastModifiedTs
	 */
	public function __construct( $name, $path, $lastModifiedTs )
	{
		$this->name = $name;
		$this->path = $path;
		$this->lastModifiedTs = $lastModifiedTs;
	}

}
