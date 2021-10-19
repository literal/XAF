<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\OrValidator
 */
class OrValidatorTest extends ValidationTestBase
{
	/**
	 * @var OrValidator
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new OrValidator($this->getValidationService());
	}

	public function testOrCombinationFirstValid()
	{
		$this->setValid('foobar', 'foo');
		$this->setInvalid('foobar', 'bar', 'fail');

		$result = $this->object->validate('foobar', 'foo', 'bar');

		$this->assertValidationResult('foobar', $result);
	}

	public function testOrCombinationSecondValid()
	{
		$this->setInvalid('foobar', 'foo', 'fail');
		$this->setValid('foobar', 'bar');

		$result = $this->object->validate('foobar', 'foo', 'bar');

		$this->assertValidationResult('foobar', $result);
	}

	public function testOrCombinationNoneValid()
	{
		$this->setInvalid('foobar', 'foo', 'fail1');
		$this->setInvalid('foobar', 'bar', 'fail2');

		$result = $this->object->validate('foobar', 'foo', 'bar');

		$this->assertValidationError('fail2', $result);
	}
}

