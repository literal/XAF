<?php
namespace XAF\file;

use XAF\type\Image;

class ImageFileHelper
{
	/** @var FileHelper */
	protected $fileHelper;

	public function __construct( FileHelper $fileHelper )
	{
		$this->fileHelper = $fileHelper;
	}

	/**
	 * @param string $file
	 * @param string|null $purpose
	 * @param string|null $description
	 * @return Image
	 */
	public function createImageFromFile( $file, $purpose = null, $description = null )
	{
		$this->fileHelper->assertFileExists($file);
		$imageType = $this->getImageType($file);
		if( $imageType === false )
		{
			throw new FileError('not an image file', $file);
		}
		$data = $this->fileHelper->getFileContents($file);
		$mimeType = \image_type_to_mime_type($imageType);
		return new Image($data, $mimeType, $purpose, $description);
	}

	/**
	 * @param string $file
	 * @return array {'width': <int>, 'height': <int>, 'mimeType': <string>, 'sizeBytes': <int>}
	 * @throws FileError
	 */
	public function getImageFileInformation( $file )
	{
		if( !$this->isImageFile($file) )
		{
			throw new FileError('invalid image file', $file);
		}
		$result = \getimagesize($file);
		return [
			'width' => $result[0],
			'height' => $result[1],
			'mimeType' => $result['mime'],
			'sizeBytes' => $this->fileHelper->getFileSize($file),
		];
	}

	/**
	 * @param string $file
	 * @return bool
	 */
	public function isImageFile( $file )
	{
		if( $this->fileHelper->fileExists($file) )
		{
			return $this->getImageType($file) !== false;
		}
		return false;
	}

	/**
	 * @param string $file
	 * @return int|bool
	 */
	private function getImageType( $file )
	{
		return \exif_imagetype($file);
	}
}
