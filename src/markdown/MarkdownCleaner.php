<?php
namespace XAF\markdown;

/**
 * Markdown to plaintext converter
 *
 * @todo Refactor & complete, this is an incomplete quick & dirty implementation without any configuration
 *
 * To be implemented:
 *
 * - remove linebreaks except after blocks (headings, list items etc. and lines ending with two or more space chars
 * - Headings: remove #/==/--, uppercase 1. level, insert blank line after & before (unless present or beginning/end of text)
 * - Headings: Add text cha underline? Optionally?
 * - links: Transform all ID references and "[]()" links to "<url>" or "<url> (<text>)" respectively
 * - Blockquotes: indent with spaces
 *
 * Not yet:
 * - Ordered lists
 *
 */

class MarkdownCleaner
{
	/**
	 * @param string $text
	 * @return string
	 */
	public function transform( $text )
	{
		$mdParser = new MarkdownParser();
		$htmlToPlaintextConverter = new HtmlToPlaintextConverter();

		$html = $mdParser->transform($text);
		return $htmlToPlaintextConverter->convert($html);
	}
}
