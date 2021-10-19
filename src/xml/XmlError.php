<?php
namespace XAF\xml;

use XAF\exception\ValueRelatedError;

class XmlError extends ValueRelatedError
{
	private $libXmlErrors = [];

	public function __construct( array $libXmlErrors )
	{
		$this->libXmlErrors = $libXmlErrors;
		parent::__construct('Malformed XML', null, $libXmlErrors);
	}

	/**
	 * @return libXMLError[]
	 */
	public function getLibXmlErrors()
	{
		return $this->libXmlErrors;
	}
}
