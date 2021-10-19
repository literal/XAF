<?php
namespace XAF\file;

class TempFileService
{
	/** @var TempFolderManager */
	private $tempFolderManager;

	/** @var FileHelper */
	private $fileHelper;

	/** @var string */
	private $baseFolder;

	public function __construct( TempFolderManager $tempFolderManager, FileHelper $fileHelper )
	{
		$this->tempFolderManager = $tempFolderManager;
		$this->fileHelper = $fileHelper;
	}

	/**
	 * @param string $file
	 * @return string
	 */
	public function createTemporaryFileCopy( $file )
	{
		$tempFile = $this->createTemporaryFileLocation(FileNameHelper::extractExtension($file));
		$this->fileHelper->copyFile($file, $tempFile);
		return $tempFile;
	}

	/**
	 * @param string $extension
	 * @return string
	 */
	public function createTemporaryFileLocation( $extension = 'tmp' )
	{
		$tempFile = $this->createUniqueFileName($extension);
		return $this->getOrCreateBaseFolder() . '/' . $tempFile;
	}

	/**
	 * @return string
	 */
	private function getOrCreateBaseFolder()
	{
		if( $this->baseFolder === null || !$this->fileHelper->fileExists($this->baseFolder) )
		{
			$this->baseFolder = $this->tempFolderManager->createFolder();
		}
		return $this->baseFolder;
	}

	/**
	 * @param string $extension
	 * @return string
	 */
	private function createUniqueFileName( $extension )
	{
		return \uniqid('f', false) . '.' . $extension;
	}
}
