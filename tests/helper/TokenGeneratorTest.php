<?php
namespace XAF\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\helper\TokenGenerator
 */
class TokenGeneratorTest extends TestCase
{
	public function testHexTokenHasRequestedLength()
	{
		$token = TokenGenerator::generateHexToken(2827);

		$this->assertEquals(2827, \strlen($token));
	}

	public function testConsecutiveHexTokensAreDifferent()
	{
		$firstToken = TokenGenerator::generateHexToken(32);
		$secondToken = TokenGenerator::generateHexToken(32);

		$this->assertNotEquals($firstToken, $secondToken);
	}

	public function testHexTokenConstistsOnlyOfHexChars()
	{
		$token = TokenGenerator::generateHexToken(128);

		$this->assertStringOnlyConsistsOfChars('0123456789abcdef', $token);
	}

	public function testUrlSafeTokenHasRequestedLength()
	{
		$token = TokenGenerator::generateUrlSafeToken(611);

		$this->assertEquals(611, \strlen($token));
	}

	public function testConsecutiveUrlSafeTokensAreDifferent()
	{
		$firstToken = TokenGenerator::generateUrlSafeToken(10);
		$secondToken = TokenGenerator::generateUrlSafeToken(10);

		$this->assertNotEquals($firstToken, $secondToken);
	}

	public function testUrlSafeTokenConstistsOnlyOfUrlSafeChars()
	{
		$token = TokenGenerator::generateUrlSafeToken(128);

		$this->assertStringOnlyConsistsOfChars(
			'-_0123456789' .
			'ABCDEFGHIJKLMNOPQRSTUVWXYZ' .
			'abcdefghijklmnopqrstuvwxyz',
			$token
		);
	}

	public function testAsciiTokenHasRequestedLength()
	{
		$token = TokenGenerator::generateAsciiToken(6);

		$this->assertEquals(6, \strlen($token));
	}

	public function testConsecutiveAsciiTokensAreDifferent()
	{
		$firstToken = TokenGenerator::generateAsciiToken(10);
		$secondToken = TokenGenerator::generateAsciiToken(10);

		$this->assertNotEquals($firstToken, $secondToken);
	}

	public function testAsciiTokenConstistsOnlyOfPrintableAsciiChars()
	{
		$token = TokenGenerator::generateAsciiToken(128);

		$printableAsciiChars = \range(\chr(32), \chr(126));
		$this->assertStringOnlyConsistsOfChars($printableAsciiChars, $token);
	}

	public function testGenerateTokenFromCustomCharsetHasRequestedLength()
	{
		$token = TokenGenerator::generateTokenFromCustomCharset('ABC123', 10);

		$this->assertEquals(10, \strlen($token));
	}

	public function testGenerateTokenFromCustomCharsetWorksWithUtf8Data()
	{
		$token = TokenGenerator::generateTokenFromCustomCharset('Ä', 5);

		$this->assertEquals('ÄÄÄÄÄ', $token);
	}

	public function testGenerateTokenFromCustomCharsetOnlyConsistsOfGivenCharset()
	{
		$charset = 'ABC123';

		$token = TokenGenerator::generateTokenFromCustomCharset($charset, 10);

		$this->assertStringOnlyConsistsOfChars($charset, $token);
	}

	public function testGenerateTokenFromCustomCharsetAreDifferent()
	{
		$firstToken = TokenGenerator::generateTokenFromCustomCharset('ABC132', 10);
		$secondToken = TokenGenerator::generateTokenFromCustomCharset('ABC132', 10);

		$this->assertNotEquals($firstToken, $secondToken);
	}

	public function testRandomByteStringHasRequestedLength()
	{
		$token = TokenGenerator::getRandomBytes(51);

		$this->assertEquals(51, \strlen($token));
	}

	public function testConsecutiveRandomByteStringsAreDifferent()
	{
		$firstString = TokenGenerator::getRandomBytes(32);
		$secondString = TokenGenerator::getRandomBytes(32);

		$this->assertNotEquals($firstString, $secondString);
	}

	/**
	 * @param string|array $expectedChars
	 * @param string $string
	 */
	private function assertStringOnlyConsistsOfChars( $expectedChars, $string )
	{
		if( \is_array($expectedChars) )
		{
			$expectedChars = \implode('', $expectedChars);
		}

		$length = \strlen($string);
		for( $i = 0; $i < $length; $i++ )
		{
			$char = $string[$i];
			if( !\strchr($expectedChars, $char) )
			{
				$this->fail('string "' . $string . '" contains unexpected character "' . $char . '"');
			}
		}
		// Prevent PHPUnit from complaining about no assertions performed
		$this->assertTrue(true);
	}
}
