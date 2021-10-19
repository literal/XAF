<?php
namespace XAF\view\helper;

use XAF\markdown\MarkdownParser;
use XAF\markdown\MarkdownCleaner;

/**
 * Requires Maxim S. Tsepkov's markdown library <https://github.com/garygolden/markdown-oo-php> with namespace
 * changed from \MaxTsepkov\Markdown to \Markdown.
 */
class MarkdownHelper
{
	public function md2html( $text )
	{
		$mdParser = new MarkdownParser();
		return $mdParser->transform($text);
	}

	public function md2plaintext( $text )
	{
		$mdCleaner = new MarkdownCleaner();
		return $mdCleaner->transform($text);
	}
}
