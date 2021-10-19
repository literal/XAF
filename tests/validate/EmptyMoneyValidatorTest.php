<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

class EmptyMoneyValidatorTest extends ValidationTestBase
{
	/** @var EmptyMoneyValidator */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new EmptyMoneyValidator();
	}

	static public function getValidTestTuples()
	{
		return [
			// Empty amount
			[['amount' => '', 'currency' => 'USD']],
			// Null amount
			[['amount' => null, 'currency' => 'USD']],
			// Missing amount
			[['currency' => 'USD']],
			// Empty struct
			[[]],
			// Empty value without currency
			['']
		];
	}

	/**
	 * @dataProvider getValidTestTuples
	 */
	public function testValidResults( $inputValue )
	{
		$result = $this->object->validate($inputValue);

		$this->assertValidationPassed($result);
		$this->assertNull($result->value);
	}

	static public function getInvalidTestTuples()
	{
		return [
			[['amount' => 'xxx']],
			['xxx'],
			['0'],
		];
	}

	/**
	 * @dataProvider getInvalidTestTuples
	 */
	public function testInvalidResults( $inputValue )
	{
		$result = $this->object->validate($inputValue);

		$this->assertValidationError('notEmpty', $result);
	}
}
