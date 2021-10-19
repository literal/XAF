<?php
namespace XAF\validate;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\validate\PassValidator
 */
class PassValidatorTest extends TestCase
{
	/** @var PassValidator */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new PassValidator();
	}

	public function testAnyValueIsReturnedUnchanged()
	{
		$validationResult = $this->object->validate('value');
		$this->assertEquals('value', $validationResult->value);

		$nullValidationResult = $this->object->validate(null);
		$this->assertEquals(null, $nullValidationResult->value);
	}
}
