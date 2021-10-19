<?php
namespace XAF\file;

/**
 * service for creation and removal of temporary folders
 * "cleanup" method should be called before application shutdown
 */
class TempFolderManager
{
	/** @var array **/
	private $createdFolders = [];

	/** @var FileHelper **/
	private $fileHelper;

	/** @var string **/
	private $rootPath;

	public function __construct( FileHelper $fileHelper, $rootPath )
	{
		$this->fileHelper = $fileHelper;
		$this->rootPath = $rootPath;
	}

	/**
	 * @return string
	 */
	public function createFolder()
	{
		while( !isset($tempFolder) || $this->fileHelper->fileExists($tempFolder) )
		{
			$folderName = $this->createUniqueFolderName();
			$tempFolder = $this->rootPath . '/' . $folderName;
		}
		$this->fileHelper->createDirectoryDeepIfNotExists($tempFolder);
		$this->createdFolders[] = $tempFolder;
		return $tempFolder;
	}

	/**
	 * @return string
	 */
	private function createUniqueFolderName()
	{
		return \uniqid('tmp', true);
	}

	public function cleanup()
	{
		foreach( $this->createdFolders as $folder )
		{
			$this->fileHelper->deleteRecursivelyIfExists($folder);
		}
	}
}
