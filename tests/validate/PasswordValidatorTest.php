<?php
namespace XAF\validate;

use XAF\exception\SystemError;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\PasswordValidator
 */
class PasswordValidatorTest extends ValidationTestBase
{
	/**
	 * @var PasswordValidator
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new PasswordValidator();
	}

	public function testMissingValueCausesEmptyError()
	{
		$result = $this->object->validate(null);

		$this->assertValidationError('empty', $result);
	}

	public function testInvalidComplexityRuleTokenThrowsException()
	{
		$this->expectException(SystemError::class);
		$this->object->validate('secret', 6, 'UnknownComplexityRule');
	}

	public function testValidationAllowsForMinLength()
	{
		$result = $this->object->validate('sec', 5);

		$this->assertValidationError('tooShort', $result);
	}

	public function testValidationAllowsForMaxLength()
	{
		$result = $this->object->validate('sec', 1, 'default', 2);

		$this->assertValidationError('tooLong', $result);
	}

	public function testPasswordsWithoutUpperCaseLettersAreNotValid()
	{
		$result = $this->object->validate('secret1%');

		$this->assertValidationError('passwordTooSimple', $result);
	}

	public function testPasswordsWithoutLowerCaseLettersAreNotValid()
	{
		$result = $this->object->validate('SECRET1%');

		$this->assertValidationError('passwordTooSimple', $result);
	}

	public function testPasswordsWithoutNumbersOrSpecialCharsAreNotValid()
	{
		$result = $this->object->validate('Secret');

		$this->assertValidationError('passwordTooSimple', $result);
	}

	public function testPasswordsWithAllRequiredCharacterTypesIsValid()
	{
		$result = $this->object->validate('1secRet');

		$this->assertValidationPassed($result);
	}
}

