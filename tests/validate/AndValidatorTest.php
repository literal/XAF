<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\AndValidator
 */
class AndValidatorTest extends ValidationTestBase
{
	/**
	 * @var AndValidator
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new AndValidator($this->getValidationService());
	}

	public function testAndCombinationValid()
	{
		$this->setValid('foobar', 'foo');
		$this->setValid('foobar', 'bar');

		$result = $this->object->validate('foobar', 'foo', 'bar');

		$this->assertValidationResult('foobar', $result);
	}

	public function testAndCombinationFirstInvalid()
	{
		$this->setValid('foobar', 'foo');
		$this->setInvalid('foobar', 'bar', 'failure');

		$result = $this->object->validate('foobar', 'foo', 'bar');

		$this->assertValidationError('failure', $result);
	}

	public function testAndCombinationSecondInvalid()
	{
		$this->setInvalid('foobar', 'foo', 'failure');
		$this->setValid('foobar', 'bar');

		$result = $this->object->validate('foobar', 'foo', 'bar');

		$this->assertValidationError('failure', $result);
	}

	public function testAndCombinationAllInvalid()
	{
		$this->setInvalid('foobar', 'foo', 'failure1');
		$this->setInvalid('foobar', 'bar', 'failure2');

		$result = $this->object->validate('foobar', 'foo', 'bar');

		$this->assertValidationError('failure1', $result);
	}
}

