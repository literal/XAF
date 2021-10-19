<?php
namespace XAF\http;

use XAF\exception\SystemError;

/**
 * @errorKey fileTooLarge(maxBytes)
 * @errorKey fileIncomplete
 */
class FileUpload
{
	/** @var bool */
	protected $received = false;

	protected $localPath;

	protected $remoteName;

	protected $remoteMimeType;

	/** @var int */
	protected $size = 0;

	/** @var string|null */
	protected $errorKey;

	/** @var array|null */
	protected $errorInfo;

	/**
	 * @param array|null $fileInfo The file upload hash as found in $_FILES
	 */
	public function __construct( $fileInfo )
	{
		if( !$fileInfo )
		{
			return;
		}

		$this->size = isset($fileInfo['size']) ? (int)$fileInfo['size'] : 0;
		$this->localPath = isset($fileInfo['tmp_name']) && $fileInfo['tmp_name'] !== '' ? $fileInfo['tmp_name'] : null;
		$this->remoteName = isset($fileInfo['name']) && $fileInfo['name'] !== '' ? $fileInfo['name'] : null;
		$this->remoteMimeType = isset($fileInfo['type']) && $fileInfo['type'] !== '' ? $fileInfo['type'] : null;
		$this->received = true;

		if( $fileInfo['error'] != \UPLOAD_ERR_OK )
		{
			$this->handleUploadError($fileInfo['error']);
		}

		if( $this->received && !\file_exists($this->localPath) )
		{
			$this->received = false;
		}
	}

	protected function handleUploadError( $errorNumber )
	{
		switch( $errorNumber )
		{
			case \UPLOAD_ERR_INI_SIZE:
				$this->setError('fileTooLarge', ['maxBytes' => $this->getMaxUploadSizeBytes()]);
				break;

			case \UPLOAD_ERR_PARTIAL:
				$this->setError('fileIncomplete');
				break;

			case \UPLOAD_ERR_NO_FILE:
				$this->received = false;
				break;

			case \UPLOAD_ERR_FORM_SIZE:
				// As it is *so* useless to limit the size via a form element, we will not support
				// it and refuse to handle a violation of the limit
				throw new SystemError('soft upload limit MAX_FILE_SIZE exceeded - feature not supported');

			case \UPLOAD_ERR_NO_TMP_DIR:
				throw new SystemError('no temp dir for storing uploaded file');

			case \UPLOAD_ERR_CANT_WRITE:
				throw new SystemError('failed to store uploaded file in temp dir');

			default:
				throw new SystemError('unknown file upload error', $errorNumber);
		}
	}

	protected function setError( $key, $info = [] )
	{
		$this->errorKey = $key;
		$this->errorInfo = $info;
	}

	protected function getMaxUploadSizeBytes()
	{
		$sizeExpr = \ini_get('upload_max_filesize');
		switch( \substr($sizeExpr, -1) )
		{
			case 'M': case 'm': return (int)$sizeExpr * 1048576;
			case 'K': case 'k': return (int)$sizeExpr * 1024;
			case 'G': case 'g': return (int)$sizeExpr * 1073741824;
		}
		return $sizeExpr;
	}

	public function received()
	{
		return $this->received;
	}

	function hasError()
	{
		return $this->errorKey !== null;
	}

	function getErrorKey()
	{
		return $this->errorKey;
	}

	function getErrorInfo()
	{
		return $this->errorInfo;
	}

	public function getSize()
	{
		return $this->size;
	}

	public function getLocalPath()
	{
		return $this->localPath;
	}

	public function getRemoteName()
	{
		return $this->remoteName;
	}

	public function getRemoteMimeType()
	{
		return $this->remoteMimeType;
	}

	public function moveTo( $destination )
	{
		if( !$this->received() || $this->hasError() )
		{
			return false;
		}

		if( !\move_uploaded_file($this->getLocalPath(), $destination) )
		{
			throw new SystemError('failed to move uploaded file', $destination, 'source: ' . $this->getLocalPath());
		}
		return true;
	}

	public function getContents()
	{
		if( !$this->received() || $this->hasError() )
		{
			return null;
		}

		$cont = @\file_get_contents($this->getLocalPath());

		if( $cont === false )
		{
			throw new SystemError('failed to read uploaded file', $this->getLocalPath());
		}

		return $cont;
	}
}
