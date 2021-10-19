<?php
namespace XAF\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\helper\KeyValueStringHelper
 */
class KeyValueStringHelperTest extends TestCase
{
	static public function getDecodeTestTuples()
	{
		return [
			// Different operators, delimiters and whitespace:
			['foo = bar, boom = quux', ['foo' => 'bar', 'boom' => 'quux']],
			['foo=bar&boom=quux', ['foo' => 'bar', 'boom' => 'quux']],
			['foo: bar, boom: quux', ['foo' => 'bar', 'boom' => 'quux']],
			['foo : "bar" ; boom : "quux"', ['foo' => 'bar', 'boom' => 'quux']],
			["foo : 'bar' ; boom : 'quux'", ['foo' => 'bar', 'boom' => 'quux']],
			["foo: bar\r\nboom = quux", ['foo' => 'bar', 'boom' => 'quux']],

			// Escaping of quote chars inside quoted values:
			["name = 'O''Brian', number = 7", ['name' => "O'Brian", 'number' => '7']],
			['name = "O\'Brian"", number = 7', ['name' => "O'Brian", 'number' => '7']],
			['name = "The ""Foo"""', ['name' => 'The "Foo"']],
			['name = \'The "Foo"\'', ['name' => 'The "Foo"']],

			// Whitespace in unquoted values:
			[' a = 1 2 3 , b = x y z ', ['a' => '1 2 3', 'b' => 'x y z']],

			// Surrounding whitespace is preserved in quoted values:
			[' a = " x " ', ['a' => ' x ']],

			// Empty values are allowed
			[' a = "", b=&c= ', ['a' => '', 'b' => '', 'c' => '']],

			// Invalid parts are ignored:
			[' BAD foo = bar, WTF, boom = quux; OMG ', ['foo' => 'bar', 'boom' => 'quux']],

			// No valid key value pairs produce empty hash:
			[' BAD WTF, OMG &', []],
		];
	}

	/**
	 * @dataProvider getDecodeTestTuples
	 */
	public function testDecode( $string, array $expectedResult )
	{
		$result = KeyValueStringHelper::decode($string);

		$this->assertEquals($expectedResult, $result);
	}

	static public function getEncodeTestTuples()
	{
		return [
			// Plain string values:
			[['foo' => 'bar', 'boom' => 'quux'], 'foo = "bar", boom = "quux"'],

			// Numbers and empty strings are not quoted:
			[['foo' => '7199', 'boom' => ''], 'foo = 7199, boom = '],

			// Double quotes are escaped:
			[['foo' => '"Foo" value'], 'foo = """Foo"" value"'],

			// Single quotes are not escaped:
			[['foo' => 'O\'Brian'], 'foo = "O\'Brian"'],

			// Empty hash produces empty string:
			[[], '']
		];
	}

	/**
	 * @dataProvider getEncodeTestTuples
	 */
	public function testEncode( array $hash, $expectedResult )
	{
		$result = KeyValueStringHelper::encode($hash);

		$this->assertEquals($expectedResult, $result);
	}
}
