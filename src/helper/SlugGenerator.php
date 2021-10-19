<?php
namespace XAF\helper;

use XAF\helper\AsciiTransliterator;

/**
 * Creates URL-safe versions of titles, names etc.
 */
class SlugGenerator
{
	/**
	 * @param string $text
	 * @param string $wordSeparator
	 * @return string
	 */
	static public function generateSlug( $text, $wordSeparator = '-' )
	{
		$slug = AsciiTransliterator::transliterate($text);
		$slug = \strtolower($slug);
		$slug = \preg_replace('/[^a-z0-9]+/', $wordSeparator, $slug);
		$slug = \trim($slug, $wordSeparator);
		return $slug;
	}
}

