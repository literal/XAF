<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\StringValidator
 */
class StringValidatorTest extends ValidationTestBase
{
	/**
	 * @var StringValidator
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new StringValidator;
	}

	public function testValid()
	{
		$result = $this->object->validate('foobar');

		$this->assertValidationResult('foobar', $result);
	}

	public function testEmptyInvalid()
	{
		$result = $this->object->validate('');

		$this->assertValidationErrorAndInfo('empty', [], $result);
	}

	public function testEmptyValidIfMinLengthZero()
	{
		$result = $this->object->validate('', 0);

		$this->assertValidationResult('', $result);
	}

	public function testResultConvertedToString()
	{
		$result = $this->object->validate(null, 0);

		$this->assertValidationResult('', $result);
	}

	public function testInvalidUtf8SequenceIsRemoved()
	{
		$result = $this->object->validate("1\xbf2", 0);

		$this->assertValidationResult('12', $result);
	}

	public function testWhitespaceNotTrimmed()
	{
		$result = $this->object->validate(' foobar ');

		$this->assertValidationResult(' foobar ', $result);
	}

	public function testMinLengthPassed()
	{
		$result = $this->object->validate('foobar', 3);

		$this->assertValidationPassed($result);
	}

	public function testMinLengthViolated()
	{
		$result = $this->object->validate('foobar', 10);

		$this->assertValidationErrorAndInfo('tooShort', ['min' => 10, 'actual' => 6], $result);
	}

	public function testMaxLengthPassed()
	{
		$result = $this->object->validate('2', 0, 5);

		$this->assertValidationPassed($result);
	}

	public function testMaxLengthViolated()
	{
		$result = $this->object->validate('foobar', 0, 4);

		$this->assertValidationErrorAndInfo('tooLong', ['max' => 4, 'actual' => 6], $result);
	}

	public function testUtf8CharsCountAsOne()
	{
		$result = $this->object->validate('äöü', 4);

		$this->assertValidationErrorAndInfo('tooShort', ['min' => 4, 'actual' => 3], $result);
	}

	public function testCarriageReturnCharsAreStripped()
	{
		$result = $this->object->validate("line 1\r\nline 2\nline\r3");

		$this->assertValidationResult("line 1\nline 2\nline3", $result);
	}
}

