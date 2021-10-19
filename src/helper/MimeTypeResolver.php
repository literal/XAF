<?php
namespace XAF\helper;

use XAF\file\FileNameHelper;

class MimeTypeResolver
{
	/** @var array **/
	private $mimeTypeToExtensionMap;

	/** @var array **/
	private $extensionToMimeTypeMap;

	/**
	 * @param array $mimeTypeToExtensionMap hash mapping the lowercase file name extension to a mime type
	 * {
	 *	 mime/type: "ext",
	 *	 image/png: "png",
	 *	 image/x-png: "png",
	 *	 ...
	 * }
	 * @param array $extensionToMimeTypeMap hash mapping the lowercase mime type to a file name extension
	 * {
	 *	 ext: "mime/type",
	 *	 mp3: "audio/mpeg",
	 *	 jpg: "image/jpeg",
	 *	 ...
	 * }
	 */
	public function __construct( array $mimeTypeToExtensionMap, array $extensionToMimeTypeMap )
	{
		$this->mimeTypeToExtensionMap = $mimeTypeToExtensionMap;
		$this->extensionToMimeTypeMap = $extensionToMimeTypeMap;
	}

	/**
	 * @param string $fileName
	 * @return string
	 */
	public function getMimeTypeFromFileName( $fileName )
	{
		$extension = \strtolower(FileNameHelper::extractExtension($fileName));
		return isset($this->extensionToMimeTypeMap[$extension])
			? $this->extensionToMimeTypeMap[$extension]
			: 'application/octet-stream';
	}

	/**
	 * @param string $mimeType
	 * @return string|null
	 */
	public function getDefaultFileNameExtensionFromMimeType( $mimeType )
	{
		return isset($this->mimeTypeToExtensionMap[\strtolower($mimeType)])
			? $this->mimeTypeToExtensionMap[\strtolower($mimeType)]
			: null;
	}
}
