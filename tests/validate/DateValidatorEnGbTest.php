<?php
namespace XAF\validate;

require_once 'DateValidatorTestBase.php';

/**
 * @covers \XAF\validate\DateValidatorEnGb
 */
class DateValidatorEnGbTest extends DateValidatorTestBase
{
	/**
	 * @var DateValidator
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new DateValidatorEnGb;
	}

	public function testValid()
	{
		$result = $this->object->validate('22/06/2010');

		$this->assertValidationResult('2010-06-22', $result);
	}

	public function testLeadingZeroesAreOptional()
	{
		$result = $this->object->validate('2/6/2010');

		$this->assertValidationResult('2010-06-02', $result);
	}

	public function testShortYear()
	{
		$result = $this->object->validate('22/06/10');

		$this->assertValidationResult('2010-06-22', $result);
	}

	public function testInvalidFormat()
	{
		$result = $this->object->validate('1-2-3');

		$this->assertValidationErrorAndInfo('invalidDateFormat', ['expected' => 'DD/MM/YYYY'], $result);
	}

	public function testInvalidUtf8Sequence()
	{
		$result = $this->object->validate("01/01/2021\xBF");

		$this->assertValidationErrorAndInfo('invalidDateFormat', ['expected' => 'DD/MM/YYYY'], $result);
	}
}

