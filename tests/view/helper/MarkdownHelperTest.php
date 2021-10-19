<?php
namespace XAF\view\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\view\helper\MarkdownHelper
 */
class MarkdownHelperTest extends TestCase
{
	/** @var MarkdownHelper */
	private $object;

	protected function setUp(): void
	{
		$this->object = new MarkdownHelper();
	}

	static public function markdownToHtmlTestTupleDataProvider()
	{
		return [
			// Paragraphs & Linebreaks
			['Foo', "<p>Foo</p>\n"],
			["Foo\nBar", "<p>Foo<br>\nBar</p>\n"],
			// Lists
			['+ Foo', "<ul>\n<li>Foo</li>\n</ul>\n"],
			['* Foo', "<ul>\n<li>Foo</li>\n</ul>\n"],
			['- Foo', "<ul>\n<li>Foo</li>\n</ul>\n"],
			["1. Foo\n2. Bar\n2. Baz", "<ol>\n<li>Foo  </li>\n<li>Bar  </li>\n<li>Baz</li>\n</ol>\n"],
			// Indentation
			['> Foo', "<blockquote>\n  <p>Foo</p>\n</blockquote>\n"],
			// Links
			['<http://www.foo.de>', "<p><a href=\"http://www.foo.de\">http://www.foo.de</a></p>\n"],
			['<foo@bar.de>', "<p><a href=\"&#x6d;&#x61;&#105;&#108;&#x74;&#x6f;&#58;&#102;&#111;&#x6f;&#x40;&#98;&#97;r&#x2e;&#x64;&#101;\">&#102;&#111;&#x6f;&#x40;&#98;&#97;r&#x2e;&#x64;&#101;</a></p>\n"],
			['[Foo](http://www.foo.de)', "<p><a href=\"http://www.foo.de\">Foo</a></p>\n"],
			["[Foo][1]\n[1]:http://www.foo.de", "<p><a href=\"http://www.foo.de\">Foo</a>  </p>\n"],
			// Headlines
			['# Foo', "<h1>Foo</h1>\n"],
			['## Foo', "<h2>Foo</h2>\n"],
			['### Foo', "<h3>Foo</h3>\n"],
			["Foo\n===", "<h1>Foo</h1>\n"],
			["Foo\n---", "<h2>Foo</h2>\n"],
			// Emphasis
			['*Foo*', "<p><em>Foo</em></p>\n"],
			['**Foo**', "<p><strong>Foo</strong></p>\n"],
			['***Foo***', "<p><strong><em>Foo</em></strong></p>\n"],
			['_Foo_', "<p><em>Foo</em></p>\n"],
			['__Foo__', "<p><strong>Foo</strong></p>\n"],
			['___Foo___', "<p><strong><em>Foo</em></strong></p>\n"],
			// Code Blocks
			["Foo\n\n\tBar", "<p>Foo  </p>\n\n<pre><code>Bar\n</code></pre>\n"],
		];
	}

	/**
	 * @dataProvider markdownToHtmlTestTupleDataProvider
	 */
	public function testMd2html( $markdownText, $expectedText )
	{
		$result = $this->object->md2html($markdownText);

		$this->assertEquals($expectedText, $result);
	}

	static public function markdownToPlaintextTestTupleDataProvider()
	{
		return [
			// Paragraphs & Linebreaks
			['Foo', 'Foo'],
			["Foo\nBar", "Foo\nBar"],
			// Indentation
			['> Foo', '    Foo'],
			// Links
			['<http://www.foo.de>', 'http://www.foo.de'],
			['<foo@bar.de>', 'foo@bar.de'],
			['[Foo](http://www.foo.de)', 'Foo (http://www.foo.de)'],
			["[Foo][1]\n[1]:http://www.foo.de", 'Foo (http://www.foo.de)'],
			// Headlines
			[
				'# Foo',
				'*******************************************************************************' . "\n" .
				'FOO' . "\n" .
				'*******************************************************************************'
			],
			[
				'## Foo',
				'FOO' . "\n" .
				'==============================================================================='
			],
			[
				'###Foo',
				'FOO' . "\n" .
				'-------------------------------------------------------------------------------'
			],
			[
				"Foo\n===",
				'*******************************************************************************' . "\n" .
				'FOO' . "\n" .
				'*******************************************************************************'
			],
			[
				"Foo\n---",
				'FOO' . "\n" .
				'==============================================================================='
			]
		];
	}

	/**
	 * @dataProvider markdownToPlaintextTestTupleDataProvider
	 */
	public function testMd2plaintext( $markdownText, $expectedText )
	{
		$result = $this->object->md2plaintext($markdownText);

		$this->assertEquals($expectedText, $result);
	}
}
