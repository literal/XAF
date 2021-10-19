<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\IntValidator
 */
class IntValidatorTest extends ValidationTestBase
{
	/** @var IntValidator */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new IntValidator;
	}

	public function testValid()
	{
		$result = $this->object->validate('3866');

		$this->assertValidationResult(3866, $result);
	}

	public function testNegative()
	{
		$result = $this->object->validate('-3866');

		$this->assertValidationResult(-3866, $result);
	}

	public function testMinusNotAsFirstCharaterIsInvalid()
	{
		$result = $this->object->validate('1-3866');

		$this->assertValidationErrorAndInfo('noInt', [], $result);
	}

	public function testNoNumber()
	{
		$result = $this->object->validate('4xyz');

		$this->assertValidationErrorAndInfo('noInt', [], $result);
	}

	public function testFloatIsInvalid()
	{
		$result = $this->object->validate('6.3');

		$this->assertValidationErrorAndInfo('noInt', [], $result);
	}

	public function testRepeatedValidation()
	{
		$firstResult = $this->object->validate('1234');

		$secondResult = $this->object->validate($firstResult->value);

		$this->assertValidationResult(1234, $secondResult);
	}
}

