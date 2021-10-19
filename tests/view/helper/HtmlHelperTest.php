<?php
namespace XAF\view\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\view\helper\HtmlHelper
 */
class HtmlHelperTest extends TestCase
{
	/** @var HtmlHelper */
	private $object;

	protected function setUp(): void
	{
		$this->object = new HtmlHelper();
	}

	public function testEscape()
	{
		$result = $this->object->escape('"foo" < & > \'bar\'');

		$this->assertEquals("&quot;foo&quot; &lt; &amp; &gt; 'bar'", $result);
	}

	public function testNl2brInsertsBrTagBeforeLineEndings()
	{
		$result = $this->object->nl2br('foo' . "\r\n" . 'bar' . "\n" . 'boom');

		$this->assertEquals('foo<br>' . "\r\n" . 'bar<br>' . "\n" . 'boom', $result);
	}

	public function testNl2pWrapsLinesInParagraphTags()
	{
		$result = $this->object->nl2p('foo' . "\r\n" . 'bar' . "\n" . 'boom');

		$this->assertEquals("<p>foo</p>\r\n" . "<p>bar</p>\n" . '<p>boom</p>', $result);
	}

	public function testNl2pUsesCustomTagsIfProvided()
	{
		$result = $this->object->nl2p('foo' . "\r\n" . 'bar' . "\n" . 'boom', '<div>', '</div>');

		$this->assertEquals('<div>foo</div>' . "\r\n" . '<div>bar</div>' . "\n" . '<div>boom</div>', $result);
	}
}
