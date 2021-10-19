<?php
namespace XAF\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\helper\CodeGeneratorHelper
 */
class CodeGeneratorHelperTest extends TestCase
{
	public function testToUnderscoreIdentifier()
	{
		$result = CodeGeneratorHelper::toUnderscoreIdentifier('foo12Bar');

		$this->assertEquals('foo12bar', $result);
	}

	public function testToUnderscoreIdentifierConvertsAnyNumberOfNonAlphanumericCharsToSingleUnderscore()
	{
		$result = CodeGeneratorHelper::toUnderscoreIdentifier('foo *-Bar');

		$this->assertEquals('foo_bar', $result);
	}

	public function testToUnderscoreIdentifierIgnoresLeadingAndTrailingNonAlphanumericChars()
	{
		$result = CodeGeneratorHelper::toUnderscoreIdentifier('(foo, ');

		$this->assertEquals('foo', $result);
	}

	public function testToUnderscoreTreatsNonAsciiCharsAsSeparators()
	{
		$result = CodeGeneratorHelper::toUnderscoreIdentifier('xäx');

		$this->assertEquals('x_x', $result);
	}

	public function testToUnderscoreIdentifierReturnsEmptyStringWhenNoAlphanumericCharsPresent()
	{
		$result = CodeGeneratorHelper::toUnderscoreIdentifier('-');

		$this->assertEquals('', $result);
	}

	public function testToTitleCaseIdentifierTreatsAnyNumberOfNonAlphanumericCharsAsWordBoundary()
	{
		$result = CodeGeneratorHelper::toTitleCaseIdentifier('foo -* bar');

		$this->assertEquals('FooBar', $result);
	}

	public function testToTitleCaseIdentifierIgnoresLeadingAndTrailingNonAlphanumericChars()
	{
		$result = CodeGeneratorHelper::toTitleCaseIdentifier('(foo, ');

		$this->assertEquals('Foo', $result);
	}

	public function testToTitleCaseIdentifierReturnsEmptyStringWhenNoAlphanumericCharsPresent()
	{
		$result = CodeGeneratorHelper::toTitleCaseIdentifier('-');

		$this->assertEquals('', $result);
	}

	public function testToCamelCaseIdentifierTreatsAnyNumberOfNonAlphanumericCharsAsWordBoundary()
	{
		$result = CodeGeneratorHelper::toCamelCaseIdentifier('foo -* bar');

		$this->assertEquals('fooBar', $result);
	}

	public function testToCamelCaseIdentifierIgnoresLeadingAndTrailingNonAlphanumericChars()
	{
		$result = CodeGeneratorHelper::toCamelCaseIdentifier('(foo, ');

		$this->assertEquals('foo', $result);
	}

	public function testToCamelCaseIdentifierReturnsEmptyStringWhenNoAlphanumericCharsPresent()
	{
		$result = CodeGeneratorHelper::toCamelCaseIdentifier('-');

		$this->assertEquals('', $result);
	}

	public function testCamelCaseToWords()
	{
		$result = CodeGeneratorHelper::camelCaseToWords('fooBarBoomBAz');

		$this->assertEquals('foo bar boom b az', $result);
	}

	public function testCamelCaseToWordsTreatsDigitsLikeLowerCaseLetters()
	{
		$result = CodeGeneratorHelper::camelCaseToWords('foo12Bar');

		$this->assertEquals('foo12 bar', $result);
	}

	public function testCamelCaseToWordsWorksWithUtf8Characters()
	{
		$result = CodeGeneratorHelper::camelCaseToWords('ÄtzÜtzÖtz');

		$this->assertEquals('ätz ütz ötz', $result);
	}

	public function testRegexEscapeEscapesRegexSpecialCharsButNotSlashes()
	{
		$result = CodeGeneratorHelper::regexEscape('[)/');

		$this->assertEquals('\\[\\)/', $result);
	}

	public function testToPhpStringLiteralEscapesSlashesAndSurroundsWithQuotes()
	{
		$result = CodeGeneratorHelper::toPhpStringLiteral("foo 'bar' \\boom");

		$this->assertEquals("'foo \\'bar\\' \\\\boom'", $result);
	}
}
