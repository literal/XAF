<?php
namespace XAF\type;

/**
 * data object holding binary image data and some meta data on the image
 */
class Image
{
	/** @var string|null */
	public $data;

	/** @var string|null */
	public $mimeType;

	/** @var string|null */
	public $purpose;

	/** @var string|null */
	public $description;

	/**
	 * @param string|null $data
	 * @param string|null $mimeType
	 * @param string|null $purpose
	 * @param string|null $description
	 */
	public function __construct( $data = null, $mimeType = null, $purpose = null, $description = null )
	{
		$this->data = $data;
		$this->mimeType = $mimeType;
		$this->purpose = $purpose;
		$this->description = $description;
	}
}
