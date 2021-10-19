<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\DurationValidator
 */
class DurationValidatorTest extends ValidationTestBase
{
	/** @var DurationValidator */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new DurationValidator;
	}

	public function testValidDurationIsAccepted()
	{
		$result = $this->object->validate('2:33');
		$this->assertValidationResult(153, $result);
	}

	public function testIntegerIsReturned()
	{
		$result = $this->object->validate('54:10');
		$this->assertIsInt($result->value);
	}

	public function testLeadingZeroesAreNotInterpretedAsOctalNumbers()
	{
		$result = $this->object->validate('012:09');
		$this->assertValidationResult(729, $result);
	}

	public function testSecondsMustBeTwoFigureNumber()
	{
		$result = $this->object->validate('1:1');
		$this->assertValidationError('invalidDuration', $result);

		$result = $this->object->validate('1:001');
		$this->assertValidationError('invalidDuration', $result);
	}

	public function testSecondsMustNotBeGreaterThan59()
	{
		$result = $this->object->validate('1:60');
		$this->assertValidationError('invalidDuration', $result);
	}

	public function testMinutesAreRequired()
	{
		$result = $this->object->validate(':22');
		$this->assertValidationError('invalidDuration', $result);
	}

	public function testMinutesCanBeZero()
	{
		$result = $this->object->validate('0:22');
		$this->assertValidationResult(22, $result);
	}

	public function testRepeatedValidation()
	{
		$firstResult = $this->object->validate('12:34');

		$secondResult = $this->object->validate($firstResult->value);

		$this->assertValidationResult(754, $secondResult);
	}
}

