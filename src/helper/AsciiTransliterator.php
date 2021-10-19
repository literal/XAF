<?php
namespace XAF\helper;

/**
 * Language agnostic, best-effort UTF-8 to ASCII transliteration.
 *
 * Original character transliteration tables taken from Perl module Text::Unidecode:
 *   Copyright 2001, Sean M. Burke <sburke@cpan.org>, all rights reserved.
 *
 * Character tables were adapted to provide the common local transliterations for German, Danish and
 * Norwegian ( e. g. 'ä' -> 'ae' instead of 'ä' -> 'a').
 */
class AsciiTransliterator
{
	static private $mapFolder = 'transliteration_maps';
	static private $map = [];

	/**
	 * @param string $text
	 * @return string
	 */
	static public function transliterate( $text )
	{
		$result = \preg_replace_callback(
			'/[\\xC0-\\xDF][\\x80-\\xBF]|[\\xE0-\\xEF][\\x80-\\xBF]{2}|[\\xF0-\\xF4][\\x80-\\xBF]{3}/',
			[__CLASS__, 'replaceUtf8Char'],
			$text
		);
		// Some replacements have a trailing space (e. g. Chinese syllable transliterations)
		return \rtrim($result, ' ');
	}

	/**
	 * preg_replace callback
	 *
	 * @param array $matches
	 * @return string
	 */
	protected static function replaceUtf8Char( array $matches )
	{
		$utf16Char = \mb_convert_encoding($matches[0], 'UTF-16', 'UTF-8');
		if( \strlen($utf16Char) != 2 )
		{
			return '?';
		}

		$highByte = \ord($utf16Char[0]);
		$lowByte = \ord($utf16Char[1]);
		return self::loadMapSection($highByte) ? self::$map[$highByte][$lowByte] : '?';
	}

	/**
	 * @param int $highByte
	 * @return bool
	 */
	private static function loadMapSection( $highByte )
	{
		if( isset(self::$map[$highByte]) )
		{
			return true;
		}

		$file = __DIR__ . '/' . self::$mapFolder . '/' . \sprintf('x%02x.php', $highByte);
		if( \file_exists($file) )
		{
			self::$map[$highByte] = require $file;
			return true;
		}

		return false;
	}
}

