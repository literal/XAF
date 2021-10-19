<?php
namespace XAF\helper;

/**
 * Generator for random tokens to be used as session ID, salt, access code etc.
 */
class TokenGenerator
{
	/**
	 * Generate random token in hex presentation, i.e. consisting of '0' to '9' and 'a' - 'f'
	 *
	 * @param int $charCount
	 * @return string
	 */
	static public function generateHexToken( $charCount )
	{
		$result = '';
		while( \strlen($result) < $charCount )
		{
			$randomBytes = self::getRandomBytes(64);
			$result .= \hash('sha256', $randomBytes);
		}
		return \substr($result, 0, $charCount);
	}

	/**
	 * Generate random token of characters safe for usage in URLs/URL-encoded strings
	 *
	 * @param int $charCount
	 * @return string
	 */
	static public function generateUrlSafeToken( $charCount )
	{
		$result = '';
		while( \strlen($result) < $charCount )
		{
			$randomBytes = self::getRandomBytes(64);
			$binaryHash = \hash('sha256', $randomBytes, true);
			$result .= self::urlSafeEncode($binaryHash);
		}
		return \substr($result, 0, $charCount);
	}

	/**
	 * @param string $binaryString
	 * @return string
	 */
	static private function urlSafeEncode( $binaryString )
	{
		$base64string = \base64_encode($binaryString);
		// Apart from letters and digits Base64 can contain '/', '+' and '='
		// '/' and '+' are not URL-safe (i.e. would have to be %-encoded) and will thus be replaced
		// '=' is used for padding and will be removed
		return \strtr($base64string, ['/' => '-', '+' => '_', '=' => '']);
	}

	/**
	 * Generate random token of printable ASCII characters
	 *
	 * @param int $charCount
	 * @return string
	 */
	static public function generateAsciiToken( $charCount )
	{
		return self::generateTokenFromCustomCharset(
			' !\\"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~',
			$charCount
		);
	}

	/**
	 * @param string $charset
	 * @param int $charCount
	 * @return string
	 */
	static public function generateTokenFromCustomCharset( $charset, $charCount )
	{
		$charsetSize = \mb_strlen($charset);
		// We want equal distribution of chars from charset.
		// If charset contains 100 chars, they would be mapped to random bytes 0..99
		// as well as 100..199 while random bytes 200..255 should be skipped, because they
		// would result in the predominance of the first 56 chars from charset.
		$maxRandomByte = (\floor(256 / $charsetSize) * $charsetSize) - 1;
		$result = '';
		$generatedCount = 0;
		while( true )
		{
			$randomBytes = self::getRandomBytes(64);
			for( $i = 0; $i < 64; $i++ )
			{
				if( $generatedCount >= $charCount )
				{
					return $result;
				}

				$c = \ord($randomBytes[$i]);
				if( $c > $maxRandomByte )
				{
					continue;
				}
				$charIndex = $c % $charsetSize;
				$result .= \mb_substr($charset, $charIndex, 1);
				$generatedCount++;
			}
		}
	}

	/**
	 * @param int $count
	 * @return string
	 */
	static public function getRandomBytes( $count )
	{
		if( self::isUnixUrandomAvailable() )
		{
			return self::getUnixUrandomBytes($count);
		}

		return self::generatePseudoRandomBytes($count);
	}

	/**
	 * @return bool
	 */
	static private function isUnixUrandomAvailable()
	{
		return \is_readable('/dev/urandom');
	}

	/**
	 * @param int $count
	 * @return string
	 */
	static private function getUnixUrandomBytes( $count )
	{
		$fh = \fopen('/dev/urandom', 'rb');
		$result = \fread($fh, $count);
		\fclose($fh);
		return $result;
	}

	/**
	 * @param int $count
	 * @return string
	 */
	static private function generatePseudoRandomBytes( $count )
	{
		$result = '';
		for( $i = 0; $i < $count; $i++ )
		{
			$number = \mt_rand(0, 255);
			$result .= \chr($number);
		}
		return $result;
	}
}
