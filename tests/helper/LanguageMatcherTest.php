<?php
namespace XAF\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\helper\LanguageMatcher
 */
class LanguageMatcherTest extends TestCase
{
	static public function getTestTuples()
	{
		return [

			// Even though there is no exact match for the first preferred language "en-NZ", it should
			// win over the second preference "fr-fr" if it can be at least partially fulfilled
			[['en-NZ', 'fr-fr'], ['fr-fr', 'de', 'en-us', 'en-gb', 'en'], 'en'],

			// As there is no match at all for "en-NZ", the helper should move on to "fr-fr"
			[['en-NZ', 'fr-fr'], ['de-ch', 'fr', 'de'], 'fr'],

			// The first available language shall be used if there is no match at all
			[['en-NZ', 'fr-fr'], ['de-ch', 'it-it'], 'de-ch'],

			// The first available language shall also be used, if there is no preference
			[[], ['de-ch'], 'de-ch'],

			// null shall be returned if there is no available language
			[['de-de'], [], null],

			// When several avalilable languages do match, the most specific match shall win:
			// 'de-de' vs. 'de-at' is counted as *two* differences (from the common part 'de')
			// while 'de-de' vs. 'de' only counts as one difference and is thus a better match
			[['de-de'], ['de-at', 'de'], 'de'],
			[['de-de'], ['de-at', 'de', 'de-de'], 'de-de'],
			[['de'], ['de-at', 'de-ch', 'de'], 'de'],

			// Among matches of equal quality, the first available one shall be returned
			[['de-de'], ['de-at', 'de-ch'], 'de-at'],
			[['de'], ['de-at', 'de-ch'], 'de-at']
		];
	}

	/**
	 * @dataProvider getTestTuples
	 */
	public function testLanguageMatching( $preferredLanguageTags, $availableLanguageTags, $expectedMatch )
	{
		$bestMatch = LanguageMatcher::findBestAvailableLanguage($preferredLanguageTags, $availableLanguageTags);

		if( $expectedMatch === null )
		{
			$this->assertNull($bestMatch);
		}
		else
		{
			$this->assertEquals($expectedMatch, $bestMatch);
		}
	}
}
