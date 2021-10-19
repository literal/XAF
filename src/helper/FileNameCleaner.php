<?php
namespace XAF\helper;

use XAF\helper\AsciiTransliterator;

/**
 * Replace or remove all characters from a string that may be problematic in a file name
 */
class FileNameCleaner
{
	/**
	 * @param string $fileName
	 * @return string
	 */
	static public function clean( $fileName )
	{
		$fileName = AsciiTransliterator::transliterate($fileName);
		$fileName = self::replaceNonFileNameCharacters($fileName);
		$fileName = self::mergeMultipleWhitespaceCharacters($fileName);
		// trailing dot is not allowed under windows
		return \rtrim(\trim($fileName), ' .');
	}

	/**
	 * @param string $fileName
	 * @return string
	 */
	static protected function replaceNonFileNameCharacters( $fileName )
	{
		return \strtr(
			$fileName,
			[
				// special characters are replaced: the following List allows for the most popular
				// FAT, NTFS, ext and HFS filesystems
				// Source: http://en.wikipedia.org/wiki/Comparison_of_file_systems#Limits
				'/' => '-',
				'*' => ' ',
				'\\' => '-',
				':' => ' - ',
				'"' => '\'',
				'|' => ' ',
				'?' => ' ',
				'<' => ' ',
				'>' => ' ',
				// control characters
				// http://de.wikipedia.org/wiki/Steuerzeichen
				\chr(0) => ' ',	// Null 	Nullzeichen
				\chr(1) => ' ', // SOH		Start of Heading
				\chr(2) => ' ', // STX		Start of Text
				\chr(3) => ' ',	// ETX		End of Text
				\chr(4) => ' ',	// EOT		End of Transmission
				\chr(5) => ' ',	// ENQ		Enquiry
				\chr(6) => ' ',	// ACK		Acknowledge
				\chr(7) => ' ',	// BEL		Bell
				\chr(8) => ' ',	// BS		Backspace
				\chr(9) => ' ',	// HT		Horizontal Tab
				\chr(10) => ' ',// LF		Line Feed
				\chr(11) => ' ',// VT		Vertical Tab
				\chr(12) => ' ',// FF		Form Feed
				\chr(13) => ' ',// CR		Carriage Return
				\chr(14) => ' ',// SO		Shift Out
				\chr(15) => ' ',// SI		Shift In
				\chr(16) => ' ',// DLE		Data Link Escape
				\chr(17) => ' ',// DC1		Device Control 1
				\chr(18) => ' ',// DC2		Device Control 2
				\chr(19) => ' ',// DC3		Device Control 3
				\chr(20) => ' ',// DC4		Device Control 4
				\chr(21) => ' ',// NAK		Negative Acknowledge
				\chr(22) => ' ',// SYN		Synchronous Idle
				\chr(23) => ' ',// ETB		End of Transmission Block
				\chr(24) => ' ',// CAN		Cancel 	Abbruch
				\chr(25) => ' ',// EM		End of Medium
				\chr(26) => ' ',// SUB		Substitute
				\chr(27) => ' ',// ESC		Escape
				\chr(28) => ' ',// FS		IS 	File Separator
				\chr(29) => ' ',// GS		Group Separator
				\chr(30) => ' ',// RS		Record Separator
				\chr(31) => ' '	// US		Unit Separator
			]
		);
	}

	/**
	 * @param string $fileName
	 * @return string
	 */
	static protected function mergeMultipleWhitespaceCharacters( $fileName )
	{
		return \preg_replace('/\\s+/', ' ', $fileName);
	}
}
