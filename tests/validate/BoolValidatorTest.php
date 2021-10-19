<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\BoolValidator
 */
class BoolValidatorTest extends ValidationTestBase
{
	/**
	 * @var BoolValidator
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new BoolValidator;
	}

	static function getValidBoolians()
	{
		return [
			[true],
			[17],
			['1'],
			['-2'],
			['on'],
			['yes'],
			['yEs'],
			['true'],
			['arbitrary text']
		];
	}

	/**
	 * @dataProvider getValidBoolians
	 */
	public function testValid( $input )
	{
		$result = $this->object->validate($input);

		$this->assertValidationResult(true, $result);
	}

	static function getInvalidBoolians()
	{
		return [
			[null],
			[false],
			[0],
			['0'],
			['off'],
			['OFF'],
			['oFF'],
			['no'],
			['false']
		];
	}

	/**
	 * @dataProvider getInvalidBoolians
	 */
	public function testInvalid( $input )
	{
		$result = $this->object->validate($input);

		$this->assertValidationResult(false, $result);
	}

	public function testRepeatedTrueValidation()
	{
		$firstResult = $this->object->validate('true');

		$secondResult = $this->object->validate($firstResult->value);

		$this->assertValidationResult(true, $secondResult);
	}

	public function testRepeatedFalseValidation()
	{
		$firstResult = $this->object->validate('false');

		$secondResult = $this->object->validate($firstResult->value);

		$this->assertValidationResult(false, $secondResult);
	}
}

