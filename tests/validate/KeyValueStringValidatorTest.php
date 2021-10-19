<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\KeyValueStringValidator
 */
class KeyValueStringValidatorTest extends ValidationTestBase
{
	/**
	 * @var KeyValueStringValidator
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new KeyValueStringValidator;
	}

	public function testInputIsNormalizedAndGarbageIsDiscarded()
	{
		$result = $this->object->validate('GARBAGE foo = bar, number=3, & boom:"quux ""1""" GARBAGE;');

		$this->assertValidationResult('foo = "bar", number = 3, boom = "quux ""1"""', $result);
	}

	public function testOnlyWhitespaceInputIsValidEmptyValue()
	{
		$result = $this->object->validate("		\n	 ");

		$this->assertValidationResult('', $result);
	}

	public function testOnlyGarbageInputCausesFailure()
	{
		$result = $this->object->validate('GARBAGE; foo foo');

		$this->assertValidationError('badKeyValueString', $result);
	}

	public function testRepeatedValidation()
	{
		$firstResult = $this->object->validate('foo = "bar", boom = "baz"');

		$secondResult = $this->object->validate($firstResult->value);

		$this->assertValidationResult('foo = "bar", boom = "baz"', $secondResult);
	}
}
