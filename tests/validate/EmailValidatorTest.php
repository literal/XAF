<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\EmailValidator
 */
class EmailValidatorTest extends ValidationTestBase
{
	/**
	 * @var EmailValidator
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new EmailValidator;
	}

	public function testEmpty()
	{
		$result = $this->object->validate('');

		$this->assertValidationErrorAndInfo('empty', [], $result);
	}

	static function getValidEmails()
	{
		return [
			['user@domain.top'],
			['user@domain.verylongtopleveldomain'],
			['user@sub.domain.top'],
			['first.last@domain.top'],
			['dashes-and_underscores@sub_sub.do-main.top'],
			['"crazy but legal"@domain.top'],
			['user\\@user@domain.top'] // yes, even this is possible according to RFC 2822!
		];
	}

	/**
	 * @dataProvider getValidEmails
	 */
	public function testValid( $email )
	{
		$result = $this->object->validate($email);

		$this->assertValidationResult($email, $result);
	}

	static function getInvalidEmails()
	{
		return [
			['user.domain'],		// no @ char
			['@domain.only'],		// no local part
			['user@'],				// no domain
			['Üser@domain.top'],	// local part contains non-ASCII chars
			['user@domain'],		// no dot in domain
			['user@domain.'],		// empty top-level domain
			['user.do main.top'],	// whitespace in domain name
			['user.domain.t  op'], // whitespace in top level domain
			['  user@domain.top  '] // surrounding whitespace
		];
	}

	/**
	 * @dataProvider getInvalidEmails
	 */
	public function testInvalid( $email )
	{
		$result = $this->object->validate($email);

		$this->assertValidationErrorAndInfo('invalidEmail', [], $result);
	}
}

