<?php
namespace XAF\validate;

require_once 'DateValidatorTestBase.php';

/**
 * @covers \XAF\validate\DateValidatorDe
 */
class DateValidatorDeTest extends DateValidatorTestBase
{
	/**
	 * @var DateValidator
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new DateValidatorDe;
	}

	public function testValid()
	{
		$result = $this->object->validate('22.06.2010');

		$this->assertValidationResult('2010-06-22', $result);
	}

	public function testWhitespaceAfterDotsAccepted()
	{
		$result = $this->object->validate('22. 06. 2010');

		$this->assertValidationResult('2010-06-22', $result);
	}

	public function testLeadingZeroesAreOptional()
	{
		$result = $this->object->validate('2.6.2010');

		$this->assertValidationResult('2010-06-02', $result);
	}

	public function testShortYear()
	{
		$result = $this->object->validate('22.06.10');

		$this->assertValidationResult('2010-06-22', $result);
	}

	public function testInvalidFormat()
	{
		$result = $this->object->validate('221.06.2010');

		$this->assertValidationErrorAndInfo('invalidDateFormat', ['expected' => 'TT.MM.JJJJ'], $result);
	}

	public function testInvalidUtf8Sequence()
	{
		$result = $this->object->validate("01.01.2021\xBF");

		$this->assertValidationErrorAndInfo('invalidDateFormat', ['expected' => 'TT.MM.JJJJ'], $result);
	}
}

