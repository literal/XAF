<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\EmptyValidator
 */
class EmptyValidatorTest extends ValidationTestBase
{
	/**
	 * @var EmptyValidator
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new EmptyValidator;
	}

	public function testEmptyStringValid()
	{
		$result = $this->object->validate('');

		$this->assertValidationResult(null, $result);
	}

	public function testNullValid()
	{
		$result = $this->object->validate(null);

		$this->assertValidationResult(null, $result);
	}

	public function testEmptyArrayValid()
	{
		$result = $this->object->validate([]);

		$this->assertValidationResult(null, $result);
	}

	public function testStringInvalid()
	{
		$result = $this->object->validate('foobar');

		$this->assertValidationErrorAndInfo('notEmpty', [], $result);
	}

	public function testZeroInvalid()
	{
		$result = $this->object->validate(0);

		$this->assertValidationErrorAndInfo('notEmpty', [], $result);
	}
}

