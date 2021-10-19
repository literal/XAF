<?php
namespace XAF\view\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\view\helper\HtmlSearchHighlightHelper
 */
class HtmlSearchHighlightHelperTest extends TestCase
{
	/** @var HtmlSearchHighlightHelper */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new HtmlSearchHighlightHelper();
	}

	public function testCurlyBracesArePutAroundPatternMatches()
	{
		// Called before the text is HTML-escaped
		$result = $this->object->putMarkers(
			'the word foo is a search word',
			// This pattern would normally be created by \XAF\helper\SearchHelper::getPregSearchPattern()
			'/\\b(foo)\\b/iu'
		);

		$this->assertEquals('the word {foo} is a search word', $result);
	}

	public function testExistingCurlyBracesAndBackslashesAreEscaped()
	{
		$result = $this->object->putMarkers(
			'curly { braces }, a \\ backslash and a foo.',
			'/\\b(foo)\\b/iu'
		);

		$expected = 'curly \\{ braces \\}, a \\\\ backslash and a {foo}.';
		$this->assertEquals($expected, $result);
	}

	public function testCurlyBracesAreReplacedByTags()
	{
		// Called after the text with the marked search hits was HTML-escaped
		$result = $this->object->applyMarkers('the word {foo} is a search word', '<span>', '</span>');

		$this->assertEquals('the word <span>foo</span> is a search word', $result);
	}

	public function testEscapedCurlyBracesAndBackslashesAreRestored()
	{
		$result = $this->object->applyMarkers(
			'curly \\{ braces \\} and a \\\\ backslash and a {foo}',
			'<span>',
			'</span>'
		);

		$this->assertEquals('curly { braces } and a \\ backslash and a <span>foo</span>', $result);
	}

	public function testMarkersAreNotAppliedButRemovedInsideHtmlTags()
	{
		$result = $this->object->applyMarkers('<no {foo} here, please!>', '<span>', '</span>');

		$this->assertEquals('<no foo here, please!>', $result);
	}

	public function testEscapedCurlyBracesAndBackslashesAreRestoredInsideHtmlTags()
	{
		$result = $this->object->applyMarkers('<curly \\{ braces \\} and a \\\\ backslash>', '<span>', '</span>');

		$this->assertEquals('<curly { braces } and a \\ backslash>', $result);
	}

}
