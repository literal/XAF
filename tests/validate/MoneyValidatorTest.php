<?php
namespace XAF\validate;

use XAF\type\Money;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\MoneyValidator
 */
class MoneyValidatorTest extends ValidationTestBase
{
	/** @var MoneyValidator */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new MoneyValidator();
	}

	static public function getValidTestTuples()
	{
		return [
			[new Money(1424, 'USD'), ['amount' => '14.24', 'currency' => 'USD']],
			// Comma is accepted as decimal separator
			[new Money(199, 'USD'), ['amount' => '1,99', 'currency' => 'USD']],
			// Amount is rounded to cents
			[new Money(157, 'CAD'), ['amount' => '1.5656', 'currency' => 'CAD']],

			// Currency is normalized
			[new Money(199, 'EUR'), ['amount' => '1.99', 'currency' => ' eur ']],
			// Default currency is used when currency is empty
			[new Money(100, 'EUR'), ['amount' => '1', 'currency' => ''], 'EUR'],
			// Default currency is used when currency is missing
			[new Money(100, 'EUR'), ['amount' => '1'], 'EUR'],
			// Default currency is used when value is only a number instead of a struct
			[new Money(100, 'EUR'), '1', 'EUR'],
		];
	}

	/**
	 * @dataProvider getValidTestTuples
	 */
	public function testValidResults( $expectedResult, $inputValue, $defaultCurrency = null )
	{
		$result = $this->object->validate($inputValue, $defaultCurrency);

		$this->assertValidationPassed($result);
		$this->assertEquals($expectedResult, $result->value);
	}

	static public function getInvalidTestTuples()
	{
		return [
			// Empty value
			['empty', ''],
			// Empty amount
			['empty', ['amount' => '', 'currency' => 'EUR']],
			// Missing amount element
			['empty', ['currency' => 'EUR']],
			// Amount is not a number
			['noNumber', ['amount' => 'foobar', 'currency' => 'EUR']],
			// Below minimum amount
			['tooSmall', ['amount' => '-1.00', 'currency' => 'EUR'], 'EUR', 0],

			// Empty currency (and no default currency)
			['missingCurrency', ['amount' => '1.50', 'currency' => '']],
			// Missing currency element (and no default currency)
			['missingCurrency', ['amount' => '1.50']],

			// Currency does not consist of three letters
			['invalidCurrency', ['amount' => '1.50', 'currency' => 'FF']],
			['invalidCurrency', ['amount' => '1.50', 'currency' => '3XX']],
		];
	}

	/**
	 * @dataProvider getInvalidTestTuples
	 */
	public function testInvalidResults( $expectedError, $inputValue, $defaultCurrency = null, $minAmount = null )
	{
		$result = $this->object->validate($inputValue, $defaultCurrency, $minAmount);

		$this->assertValidationError($expectedError, $result);
	}

	public function testRepeatedValidation()
	{
		$firstResult = $this->object->validate(['amount' => '10,50', 'currency' => 'EUR']);

		$secondResult = $this->object->validate($firstResult->value);

		$this->assertValidationPassed($secondResult);
		$this->assertEquals(new Money(1050, 'EUR'), $secondResult->value);
	}
}

