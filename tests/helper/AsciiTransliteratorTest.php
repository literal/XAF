<?php
namespace XAF\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\helper\AsciiTransliterator
 */
class AsciiTransliteratorTest extends TestCase
{
	static public function getTestTuples()
	{
		return [
			['groß', 'gross'],
			['Ä Ö Ü ä ö ü', 'Ae Oe Ue ae oe ue'], // German
			['Æ Å Ø æ å ø', 'AE Aa Oe ae aa oe'], // Danish/Norwegian
			['â é ì ç ñ', 'a e i c n'], // Various latin accented letters
			['Δ Σ τ π', 'D S t p'], // Greek
			['Д Я ж п', 'D Ia zh p'], // Cyrillic
			['北京', 'Bei Jing'], // Chinese
			['', '?'], // Chinese symbol encountered in production which is missing from the transliteration maps
			['€ £ $', 'EUR GBP $'], // Currencies
			['© ® ™', '(c) (r) (TM)'], // Symbols
		];
	}

	/**
	 * @dataProvider getTestTuples
	 */
	public function testTransliterate( $input, $expectedOutput )
	{
		$result = AsciiTransliterator::transliterate($input);

		$this->assertEquals($expectedOutput, $result);
	}
}
