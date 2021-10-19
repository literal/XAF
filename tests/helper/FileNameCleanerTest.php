<?php
namespace XAF\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\helper\FileNameCleaner
 */
class FileNameCleanerTest extends TestCase
{
	public function testConvertRegularStringReturnsSame()
	{
		$result = FileNameCleaner::clean('foo');

		$this->assertEquals('foo', $result);
	}

	public function testSpecialCharsAreTransliterated()
	{
		$result = FileNameCleaner::clean('ÖöÄäÜüÁÂÀáâàÉÊÈéêèÍÎÌíîìÓÔÒóôòÝýæëåãõñðøÆÇ€');

		// As iconv ASCII transliteration produces different results on different platforms,
		// we cannot assert the exact result here
		$this->assertContainsOnlyValidAsciiChars($result);
	}

	public function testResultIsTrimmed()
	{
		$result = FileNameCleaner::clean(' x ');

		$this->assertEquals('x', $result);
	}

	public function testTrailingDotsAreRemoved()
	{
		$result = FileNameCleaner::clean('.x.y. .');

		$this->assertEquals('.x.y', $result);
	}

	public function testMultipleWhitespaceIsCompactedIntoOneSpace()
	{
		$result = FileNameCleaner::clean("x \t\r\n   x");

		$this->assertEquals('x x', $result);
	}

	public function testControlCharactersAreReplacedBySpace()
	{
		$result = FileNameCleaner::clean('x' . \chr(1) . 'x');

		// As iconv ASCII transliteration produces different results on different platforms,
		// we cannot assert the exact result here
		$this->assertContainsOnlyValidAsciiChars($result);

		$this->assertEquals('x x', $result);
	}

	public function testForbiddenFileNameCharsAreReplacedBySpaces()
	{
		$result = FileNameCleaner::clean('x|?*<>x');

		$this->assertEquals('x x', $result);
	}

	public function testSlashesAreReplacedByDashes()
	{
		$result = FileNameCleaner::clean('A\\ B/C');

		$this->assertEquals('A- B-C', $result);
	}

	public function testColonsAreReplacedByDashesSurroundedBySpaces()
	{
		$result = FileNameCleaner::clean('A: B');

		$this->assertEquals('A - B', $result);
	}

	public function testDoubleQuotesAreReplacedBySingleQuotes()
	{
		$result = FileNameCleaner::clean('The "foo"');

		$this->assertEquals("The 'foo'", $result);
	}

	private function assertContainsOnlyValidAsciiChars( $string )
	{
		for( $i = 0, $l = \strlen($string); $i < $l; $i++ )
		{
			$charCode = \ord($string[$i]);
			if( $charCode > 127 )
			{
				$this->fail('string contains non-ASCII character #' . $charCode . ': ' . $string);
			}
			else if( $charCode < 32 )
			{
				$this->fail('string contains invalid character #' . $charCode . ': ' . $string);
			}
		}
		// Prevent PHPUnit from complaining about no assertions being performed
		$this->assertTrue(true);
	}
}
