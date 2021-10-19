<?php
namespace XAF\markdown;

use PHPUnit\Framework\TestCase;

/**
 * @todo Complete test
 *
 * @covers \XAF\markdown\HtmlToPlaintextConverter
 */
class HtmlToPlaintextConverterTest extends TestCase
{
	/** @var HtmlToPlaintextConverter */
	private $object;

	protected function setUp(): void
	{
		$this->object = new HtmlToPlaintextConverter;
	}

	public function testParagraphBoundariesAreConvertedToSingleLfBlankLines()
	{
		$result = $this->object->convert("<p>A</p><p>B</p>  \r\n  \r\n\t\t<p>C</p>");

		$this->assertEquals("A\n\nB\n\nC", $result);
	}

	public function testSpecialCharEncoding()
	{
		$result = $this->object->convert('Fööß');

		$this->assertEquals('Fööß', $result);
	}

	public function testHtmlEntityDecoding()
	{
		$result = $this->object->convert('<p>A &amp; B</p>');

		$this->assertEquals('A & B', $result);
	}

	public function testIndividuallyLabelledLinkDecoding()
	{
		$result = $this->object->convert('<a href="http://www.foo.com/bar" target="_blank">The Link</a>');

		$this->assertEquals('The Link (http://www.foo.com/bar)', $result);
	}

	public function testUrlLabelledLinkDecoding()
	{
		$result = $this->object->convert('<a href="http://www.foo.com/bar" target="_blank">www.foo.com</a>');

		$this->assertEquals('http://www.foo.com/bar', $result);
	}

	public function testUnlabelledLinkDecoding()
	{
		$result = $this->object->convert('<a href="http://www.foo.com/bar"></a>');

		$this->assertEquals('http://www.foo.com/bar', $result);
	}

	public function testHrefLessLinkDecoding()
	{
		$result = $this->object->convert('<a>boo</a>');

		$this->assertEquals('boo', $result);
	}
}
