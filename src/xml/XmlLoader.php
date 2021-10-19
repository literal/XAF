<?php
namespace XAF\xml;

use SimpleXMLElement;
use DOMDocument;

/**
 * Add consistent error handling to PHP XML parsing
 */
class XmlLoader
{
	private function __construct() {}

	/**
	 * @param DOMDocument $document
	 * @param string $xmlSource
	 */
	static public function loadXmlIntoDomDocument( DOMDocument $document, $xmlSource )
	{
		$originalInternalErrorsState = \libxml_use_internal_errors(true);
		\libxml_clear_errors();

		$loadResult = $document->loadXML($xmlSource);

		$errors = \libxml_get_errors();
		\libxml_clear_errors();
		\libxml_use_internal_errors($originalInternalErrorsState);

		if( !$loadResult )
		{
			throw new XmlError($errors);
		}
	}

	/**
	 * @param string $xmlSource
	 * @param int $libXmlOptions Bitwise OR of LIBXML_* constants
	 * @return SimpleXMLElement
	 */
	static public function createSimpleXmlElement( $xmlSource, $libXmlOptions = 0 )
	{
		$originalInternalErrorsState = \libxml_use_internal_errors(true);
		\libxml_clear_errors();

		// Unlike SimpleXMLElement::__construct() this not not throw \Exception when XML is malformed but returns false
		$result = \simplexml_load_string($xmlSource, 'SimpleXMLElement', $libXmlOptions);
		$errors = \libxml_get_errors();

		\libxml_clear_errors();
		\libxml_use_internal_errors($originalInternalErrorsState);

		if( $result === false )
		{
			throw new XmlError($errors);
		}

		return $result;
	}
}
