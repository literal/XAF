<?php
namespace XAF\validate;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\validate\DefaultValidator
 */
class DefaultValidatorTest extends TestCase
{
	/** @var DefaultValidator */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new DefaultValidator();
	}

	public function testNonEmptyValueIsResturnedUnchanged()
	{
		$validationResult = $this->object->validate('value', 'defaults');

		$this->assertEquals('value', $validationResult->value);
	}

	public function testNullValueIsReplacedByDefault()
	{
		$validationResult = $this->object->validate(null, 'default');

		$this->assertEquals('default', $validationResult->value);
	}

	public function testEmptyStringValueIsReplacedByDefault()
	{
		$validationResult = $this->object->validate('', 'default');

		$this->assertEquals('default', $validationResult->value);
	}
}
