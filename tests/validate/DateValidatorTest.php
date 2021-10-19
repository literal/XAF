<?php
namespace XAF\validate;

require_once 'DateValidatorTestBase.php';

/**
 * @covers \XAF\validate\DateValidator
 */
class DateValidatorTest extends DateValidatorTestBase
{
	/**
	 * @var DateValidator
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new DateValidator;
	}

	public function testValidDateString()
	{
		$result = $this->object->validate('2010-06-22');

		$this->assertValidationResult('2010-06-22', $result);
	}

	public function testRepeatedValidation()
	{
		$firstResult = $this->object->validate('2010-06-22');

		$secondResult = $this->object->validate($firstResult->value);

		$this->assertValidationResult('2010-06-22', $secondResult);
	}

	public function testEmpty()
	{
		$result = $this->object->validate('');

		$this->assertValidationErrorAndInfo('empty', [], $result);
	}

	public function testWhitespaceNotTrimmed()
	{
		$result = $this->object->validate(' 2010-06-22 ');

		$this->assertValidationErrorAndInfo('invalidDateFormat', ['expected' => 'YYYY-MM-DD'], $result);
	}

	public function testInvalidFormat()
	{
		$result = $this->object->validate('2010 06-22');

		$this->assertValidationErrorAndInfo('invalidDateFormat', ['expected' => 'YYYY-MM-DD'], $result);
	}

	public function testInvalidDate()
	{
		$result = $this->object->validate('2010-02-30');

		$this->assertValidationErrorAndInfo('invalidDate', [], $result);
	}
}

