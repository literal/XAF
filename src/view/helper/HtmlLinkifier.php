<?php
namespace XAF\view\helper;

/**
 * Find URLs in a string and turn them into HTML-a-href-tags
 */
class HtmlLinkifier
{
	public function linkify( $text )
	{
		// find full urls starting with 'http[s]://' or 'ftp://'
		$text = \preg_replace('!(^|[\\s])((?:http|https|ftp)://[\\S]+)!u', '\\1<a href="\\2" target="_blank">\\2</a>', $text);
		// find host names starting with 'www.'
		$text = \preg_replace('!(^|[\\s])(www\\.[\\S]+?\\.[\\S]+)!u', '\\1<a href="http://\\2" target="_blank">\\2</a>', $text);
		return $text;
	}
}
