<?php
namespace XAF\view\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\view\helper\StringHelper
 */
class StringHelperTest extends TestCase
{
	/** @var StringHelper */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new StringHelper;
	}

	public function testTruncateReturns10CharsByDefault()
	{
		$result = $this->object->truncate('abcdefghijklmnopqrstuvwxyz');

		$this->assertEquals('abcdefghij…', $result);
	}

	public function testEllipsisPostfixIsAddedWhenStringIsTruncated()
	{
		$result = $this->object->truncate('abcd', 3, '**');

		$this->assertEquals('abc**', $result);
	}

	public function testEllipsisPostfixIsNotAddedWhenStringIsNotTruncated()
	{
		$result = $this->object->truncate('abc', 3, '**');

		$this->assertEquals('abc', $result);
	}

	public function testTruncateUsesUtf8CharsInsteadOfBytes()
	{
		// string is 10 characters in 11 bytes
		$result = $this->object->truncate('123456789ö');

		$this->assertEquals('123456789ö', $result);
	}

	public function testLimitPartsLeavesStringWithoutExcessvePartCountUnchanged()
	{
		$result = $this->object->limitParts('A, B, C', 3, ',');

		$this->assertEquals('A, B, C', $result);
	}

	public function testLimitPartsRemovesPartyBeyondLimit()
	{
		$result = $this->object->limitParts('A, B, C', 2, ',', ' and others');

		$this->assertEquals('A, B and others', $result);
	}

	public function testCutInvalidRemainderReturnsValidStringUnchanged()
	{
		// Default valid chars are letters a-z, numbers, underscore and dash
		$result = $this->object->cutInvalidRemainder('Abc_123-Xyz');

		$this->assertEquals('Abc_123-Xyz', $result);
	}

	public function testCutInvalidRemainderCutsEverythingFromFirstInvalidChar()
	{
		$result = $this->object->cutInvalidRemainder('foo<xyz');

		$this->assertEquals('foo', $result);
	}

	public function testCutInvalidRemainderAcceptsCustomValidCharsPattern()
	{
		$result = $this->object->cutInvalidRemainder('foo=123:xyz', '1-3fo=');

		$this->assertEquals('foo=123', $result);
	}

	public function testCutInvalidRemainderValidCharsPatternSupportsNegativeRegexCharacterClass()
	{
		$result = $this->object->cutInvalidRemainder('abc h xyz', '^fgh');

		$this->assertEquals('abc ', $result);
	}

	public function testCutInvalidRemainderValidCharsPatternSupportsRegexCharacterClassShorthands()
	{
		$result = $this->object->cutInvalidRemainder('44 33 ab', '\\s\\d');

		$this->assertEquals('44 33 ', $result);
	}

	public function testCutInvalidRemainderReturnsEmptyStringIfInputIsEmpty()
	{
		$result = $this->object->cutInvalidRemainder('');

		$this->assertSame('', $result);
	}

	public function testCutInvalidRemainderReturnsEmptyStringIfInputStartsWithInvalidChar()
	{
		$result = $this->object->cutInvalidRemainder('+abc');

		$this->assertSame('', $result);
	}

	public function testCutInvalidRemainderReturnsEmptyStringIfInputContainsBrokenUft8()
	{
		$result = $this->object->cutInvalidRemainder("1\xbf2");

		$this->assertSame('', $result);
	}

	public function testCutInvalidRemainderWorksWithUtf8Chars()
	{
		$result = $this->object->cutInvalidRemainder('xäÄx', 'äx');

		$this->assertEquals('xä', $result);
	}

	public function testCutInvalidRemainderWithInvalidCharacterClassSyntaxTriggersWarning()
	{
		$this->expectWarning();
		$this->object->cutInvalidRemainder('(]', ']-(');
	}
}
