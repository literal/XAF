<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\NumberValidator
 */
class NumberValidatorTest extends ValidationTestBase
{
	/**
	 * @var NumberValidator
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new NumberValidator;
	}

	public function testValid()
	{
		$result = $this->object->validate('3866.114');

		$this->assertValidationResult(3866.114, $result);
	}

	public function testMinusNotAsFirstCharaterIsInvalid()
	{
		$result = $this->object->validate('1-3866');

		$this->assertValidationErrorAndInfo('noNumber', [], $result);
	}

	public function testInvalid()
	{
		$result = $this->object->validate('2e10'); // no 'e', '0x' etc.

		$this->assertValidationErrorAndInfo('noNumber', [], $result);
	}

	public function testEmpty()
	{
		$result = $this->object->validate('');

		$this->assertValidationErrorAndInfo('empty', [], $result);
	}

	public function testWhitespaceNotTrimmed()
	{
		$result = $this->object->validate(' 22 ');

		$this->assertValidationErrorAndInfo('noNumber', [], $result);
	}

	public function testLeadingZeroes()
	{
		$result = $this->object->validate('0013');

		$this->assertValidationResult(13.0, $result);
	}

	public function testNegative()
	{
		$result = $this->object->validate('-7.8');

		$this->assertValidationResult(-7.8, $result);
	}

	public function testFractionOnly()
	{
		$result = $this->object->validate('.123');

		$this->assertValidationResult(0.123, $result);
	}

	public function testMinConstraintPassed()
	{
		$result = $this->object->validate('2', 1);

		$this->assertValidationPassed($result);
	}

	public function testMinConstraintViolated()
	{
		$result = $this->object->validate('2', 5);

		$this->assertValidationErrorAndInfo('tooSmall', ['min' => 5], $result);
	}

	public function testMaxConstraintPassed()
	{
		$result = $this->object->validate('2', 0, 5);

		$this->assertValidationPassed($result);
	}

	public function testMaxConstraintViolated()
	{
		$result = $this->object->validate('17', 0, 10);

		$this->assertValidationErrorAndInfo('tooBig', ['max' => 10], $result);
	}

	public function testRepeatedValidation()
	{
		$firstResult = $this->object->validate('12.381');

		$secondResult = $this->object->validate($firstResult->value);

		$this->assertValidationResult(12.381, $secondResult);
	}
}

