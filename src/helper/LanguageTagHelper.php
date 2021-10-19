<?php
namespace XAF\helper;

class LanguageTagHelper
{
	/**
	 * @param string $languageTag
	 * @return string
	 */
	static public function normalize( $languageTag )
	{
		$languageTag = \strtolower($languageTag);
		$languageTag = \strtr($languageTag, ['_' => '-', '.' => '-', ' ' => '']);
		return $languageTag;
	}

	/**
	 * When a language tag is used to select a localized version of an object from the DI container,
	 * the tag must be transformed into object qualifier notation (dot-separated) and be appended
	 * to the object key with a dot.
	 *
	 * Examples:
	 *   'en-us' => '.en.us'
	 *   'DE' => '.de'
	 *   '' => ''
	 *
	 * @param string|null $languageTag
	 * @return string
	 */
	static public function toObjectQualifier( $languageTag )
	{
		return $languageTag
			? '.' . \str_replace('-', '.', self::normalize($languageTag))
			: '';
	}

	/**
	 * @param string|null $objectQualifier
	 * @return string|null
	 */
	static public function fromObjectQualifier( $objectQualifier )
	{
		return $objectQualifier
			? self::normalize(\str_replace('.', '-', $objectQualifier))
			: null;
	}

	/**
	 * @param string $languageTag
	 * @return array
	 */
	static public function split( $languageTag )
	{
		$languageTag = self::normalize($languageTag);
		return $languageTag !== '' ? \explode('-', $languageTag) : [];
	}
}
