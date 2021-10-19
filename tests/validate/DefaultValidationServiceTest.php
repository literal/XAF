<?php
namespace XAF\validate;

use Phake;

use XAF\di\DiContainer;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\DefaultValidationService
 */
class DefaultValidationServiceTest extends ValidationTestBase
{
	/** @var DiContainer */
	protected $containerMock;

	/** @var Validator */
	protected $validValidatorMock;

	/** @var ValidationService */
	protected $object;

	protected function setUp(): void
	{
		$this->containerMock = Phake::mock(DiContainer::class);
		$this->object = new DefaultValidationService($this->containerMock);

		$this->validValidatorMock = Phake::mock(Validator::class);
	}

	public function testSingleRule()
	{
		$this->setValidValidator('foo');

		$this->object->validate('foobar', 'foo');

		Phake::verify($this->validValidatorMock)->validate('foobar');
	}

	public function testSingleRuleWithParams()
	{
		$this->setValidValidator('foo');

		$this->object->validate('foobar', 'foo(abc, 1)');

		Phake::verify($this->validValidatorMock)->validate('foobar', 'abc', 1);
	}

	public function testSingleRuleWithNestedParams()
	{
		$this->setValidValidator('foo');

		$this->object->validate('foobar', 'foo(a(b, c()), d)');

		Phake::verify($this->validValidatorMock)->validate('foobar', 'a(b, c())', 'd');
	}

	public function testValidatorChainSuccess()
	{
		$this->setValidValidator('foo', 'foobar');

		$result = $this->object->validate('foobar', 'foo|foo|foo');

		$this->assertValidationResult('foobar', $result);
	}

	protected function setValidValidator( $validatorKey, $value = null )
	{
		Phake::when($this->containerMock)->getLocal($validatorKey)->thenReturn($this->validValidatorMock);
		Phake::when($this->validValidatorMock)->validate(Phake::anyParameters())->thenReturn(
			ValidationResult::createValid($value ?: Phake::anyParameters())
		);
	}

	public function testValidatorChainFail()
	{
		$this->setValidValidator('foo', 'foobar');
		$this->setInvalidValidator('bar', 'failure1');
		$this->setInvalidValidator('bom', 'failure2');

		$result = $this->object->validate('foobar', 'foo|bar|bom');

		$this->assertValidationError('failure1', $result);
	}

	private function setInvalidValidator( $validatorKey, $errorMessage )
	{
		$failValidatorMock = Phake::mock(Validator::class);
		Phake::when($this->containerMock)->getLocal($validatorKey)->thenReturn($failValidatorMock);
		Phake::when($failValidatorMock)->validate(Phake::anyParameters())->thenReturn(
			ValidationResult::createError($errorMessage)
		);
	}

}

