<?php
namespace XAF\view\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\view\helper\HtmlLinkifier
 */
class HtmlLinkifierTest extends TestCase
{
	/** @var HtmlLinkifier */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new HtmlLinkifier();
	}

	public function testLinkify()
	{
		$result = $this->object->linkify('www.foo.bar is a link just as http://foo.bar/ is');

		$expected =
			'<a href="http://www.foo.bar" target="_blank">www.foo.bar</a> is a link just as ' .
			'<a href="http://foo.bar/" target="_blank">http://foo.bar/</a> is';
		$this->assertEquals($expected, $result);
	}
}
