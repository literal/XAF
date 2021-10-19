<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\CountryCodeValidator
 */
class CountryCodeValidatorTest extends ValidationTestBase
{
	/**
	 * @var CountryCodeValidator
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new CountryCodeValidator;
	}

	public function testEmpty()
	{
		$result = $this->object->validate('');

		$this->assertValidationErrorAndInfo('empty', [], $result);
	}

	static function getValidCountryCodes()
	{
		return [
			['de', 'DE'],
			['DE'],
			['US'],
			['xX', 'XX'],
		];
	}

	/**
	 * @dataProvider getValidCountryCodes
	 */
	public function testValid( $tag, $expected = null )
	{
		$result = $this->object->validate($tag);

		$this->assertValidationResult($expected !== null ? $expected : $tag, $result);
	}

	static function getInvalidCountryCodes()
	{
		return [
			[' '],
			['x'],
			['X2'],
			['ABC'],
			[' DE '],
			["DE\xBF"], // invalid UTF-8 sequence
		];
	}

	/**
	 * @dataProvider getInvalidCountryCodes
	 */
	public function testInvalid( $tag )
	{
		$result = $this->object->validate($tag);

		$this->assertValidationErrorAndInfo('invalidCountryCode', [], $result);
	}
}

