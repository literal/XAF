<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\NotEmptyValidator
 */
class NotEmptyValidatorTest extends ValidationTestBase
{
	/**
	 * @var NotEmptyValidator
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new NotEmptyValidator;
	}

	public function testEmptyStringInvalid()
	{
		$result = $this->object->validate('');

		$this->assertValidationErrorAndInfo('empty', [], $result);
	}

	public function testNullInvalid()
	{
		$result = $this->object->validate(null);

		$this->assertValidationErrorAndInfo('empty', [], $result);
	}

	public function testEmptyArrayInvalid()
	{
		$result = $this->object->validate([]);

		$this->assertValidationErrorAndInfo('empty', [], $result);
	}

	public function testFalseValid()
	{
		$result = $this->object->validate(false);

		$this->assertValidationResult(false, $result);
	}

	public function testStringValid()
	{
		$result = $this->object->validate('foobar');

		$this->assertValidationResult('foobar', $result);
	}

	public function testNumberValid()
	{
		$result = $this->object->validate('0');

		$this->assertValidationResult('0', $result);
	}

}

