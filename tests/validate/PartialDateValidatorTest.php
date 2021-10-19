<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\PartialDateValidator
 */
class PartialDateValidatorTest extends ValidationTestBase
{
	/**
	 * @var PartialDateValidator
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new PartialDateValidator;
	}

	public function testValidYear()
	{
		$result = $this->object->validate('2010');

		$this->assertValidationResult('2010', $result);
	}

	public function testValidMonth()
	{
		$result = $this->object->validate('2010-06');

		$this->assertValidationResult('2010-06', $result);
	}

	public function testValidDay()
	{
		$result = $this->object->validate('2010-06-22');

		$this->assertValidationResult('2010-06-22', $result);
	}

	public function testOneFigureMonthAndDay()
	{
		$result = $this->object->validate('2010-6-9');

		$this->assertValidationResult('2010-06-09', $result);
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

