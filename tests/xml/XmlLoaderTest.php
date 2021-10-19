<?php
namespace XAF\xml;

use PHPUnit\Framework\TestCase;

use DOMDocument;

/**
 * @covers \XAF\xml\XmlLoader
 * @covers \XAF\xml\XmlError
 */
class XmlLoaderTest extends TestCase
{
	public function testLoadValidXmlIntoDomDocument()
	{
		$doc = new DOMDocument();

		XmlLoader::loadXmlIntoDomDocument(
			$doc,
			'<?xml version="1.0" encoding="utf-8"?>' . "\n"
			. '<root><child>foo</child></root>'
		);

		$this->assertEquals('root', $doc->documentElement->nodeName);
		$this->assertEquals('child', $doc->documentElement->childNodes->item(0)->nodeName);
	}

	public function testLoadInvalidXmlIntoDomDocument()
	{
		$doc = new DOMDocument();

		$this->expectException(\XAF\xml\XmlError::class);
		XmlLoader::loadXmlIntoDomDocument(
			$doc,
			'<?xml version="1.0" encoding="utf-8"?>' . "\n"
			. '<root><unclosed></root>'
		);
	}

	public function testCreateSimpleXmlElementFromValidXmlDocument()
	{
		$result = XmlLoader::createSimpleXmlElement(
			'<?xml version="1.0" encoding="utf-8"?>' . "\n"
			. '<root><child>foo</child></root>'
		);

		$this->assertEquals('foo', \strval($result->child));
	}

	public function testCreateSimpleXmlElementFromInvalidXmlDocument()
	{
		$this->expectException(\XAF\xml\XmlError::class);
		XmlLoader::createSimpleXmlElement(
			'<?xml version="1.0" encoding="utf-8"?>' . "\n"
			. '<root><unclosed></root>'
		);
	}
}
