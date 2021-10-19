<?php
namespace XAF\validate;

require_once 'DateValidatorTestBase.php';

/**
 * @covers \XAF\validate\DateValidatorEnUs
 */
class DateValidatorEnUsTest extends DateValidatorTestBase
{
	/**
	 * @var DateValidator
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new DateValidatorEnUs;
	}

	public function testValid()
	{
		$result = $this->object->validate('06/22/2010');

		$this->assertValidationResult('2010-06-22', $result);
	}

	public function testLeadingZeroesAreOptional()
	{
		$result = $this->object->validate('6/2/2010');

		$this->assertValidationResult('2010-06-02', $result);
	}

	public function testShortYear()
	{
		$result = $this->object->validate('06/22/10');

		$this->assertValidationResult('2010-06-22', $result);
	}

	public function testInvalidFormat()
	{
		$result = $this->object->validate('1-2-3');

		$this->assertValidationErrorAndInfo('invalidDateFormat', ['expected' => 'MM/DD/YYYY'], $result);
	}

	public function testInvalidUtf8Sequence()
	{
		$result = $this->object->validate("01/01/2021\xBF");

		$this->assertValidationErrorAndInfo('invalidDateFormat', ['expected' => 'MM/DD/YYYY'], $result);
	}
}

