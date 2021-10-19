<?php
namespace XAF\type;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\type\SearchRequest
 */
class SearchRequestTest extends TestCase
{
	public function testEmptyStringisEmptySearch()
	{
		$object = new SearchRequest('');
		$this->assertTrue($object->isEmpty());
	}

	public function testWhitespaceOnlyIsEmptySearch()
	{
		$object = new SearchRequest("  \n\r \t ");

		$this->assertTrue($object->isEmpty());
	}

	public function testCharactersAreNotEmptySearch()
	{
		$object = new SearchRequest('Not Empty');
		$this->assertFalse($object->isEmpty());
	}

	public function testGetPhraseReturnsOriginalPhaseUnchanged()
	{
		$object = new SearchRequest('foo?bar- ');

		$this->assertEquals('foo?bar- ', $object->getPhrase());
	}

	static public function getSearchStringToTermsTuples()
	{
		return [
			// Any number of whitespace and punctuation characters split words
			['foo bar', ['foo', 'bar']],
			['foo    bar', ['foo', 'bar']],

			// Any non-whitespace characters are considered "word" characters
			['foo!bar', ['foo!bar']],
			['-foo-bar-', ['-foo-bar-']],
			['foo! ?  bar', ['foo!', '?', 'bar']],

			// Quotes keep words together
			['"foo bar" baz', ['foo bar', 'baz']],

			// Unbalanced quotes are ignored
			['"alpha" "beta gamma', ['alpha', 'beta', 'gamma']],

			// Quoted terms consisting of whitespace only are ignored
			['"  " foo', ['foo']],

			// Quoted whitespace is collapsed to single space character
			['"foo	' . "\t\n" . ' bar"', ['foo bar']],

			// Leading and trailing whitespace inside quotes is trimmed()
			['" foo bar "', ['foo bar']]
		];
	}

	/**
	 * @dataProvider getSearchStringToTermsTuples
	 * @param string $searchPhrase
	 * @param array $terms
	 */
	public function testSearchStringIsSplitIntoTermsCorrectly( $searchPhrase, array $terms )
	{
		$object = new SearchRequest($searchPhrase);

		$this->assertEquals($terms, $object->getTerms());
	}

	public function testPregSearchPatternIsNullForEmptySearch()
	{
		$object = new SearchRequest('  " " ');

		$this->assertNull($object->getPregSearchPattern());
	}

	public static function getPregPatternTestTuples()
	{
		return [
			// Each separate word is matched
			['foo bar', 'foo'],
			['foo bar', 'bar'],

			// Matches as substring
			['foo', 'afoob'],

			// Is case-insensitive
			['foo', 'FoO'],

			// Any whitespace matches any other whitespace inside quotes
			['"foo bar"', 'something foo        bar something else'],

			// Regex special chars are escaped properly
			['foo{9}', 'foo{9} or bar?'],
		];
	}

	/**
	 * @dataProvider getPregPatternTestTuples
	 * @param string $searchPhrase
	 * @param string $subject
	 */
	public function testGeneratedPregPatternMatches( $searchPhrase, $subject )
	{
		$object = new SearchRequest($searchPhrase);

		$pregPattern = $object->getPregSearchPattern();

		$this->assertTrue(
			\preg_match($pregPattern, $subject) == 1,
			'preg pattern ' . $pregPattern . ' does not match \'' . $subject . '\''
		);
	}
}
