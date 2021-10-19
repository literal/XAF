<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\LanguageCodeValidator
 */
class LanguageCodeValidatorTest extends ValidationTestBase
{
	/**
	 * @var LanguageCodeValidator
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new LanguageCodeValidator;
	}

	public function testEmpty()
	{
		$result = $this->object->validate('');

		$this->assertValidationErrorAndInfo('empty', [], $result);
	}

	static function getValidLanguageCodes()
	{
		return [
			['ger'],
			['FRE', 'fre'],
		];
	}

	/**
	 * @dataProvider getValidLanguageCodes
	 */
	public function testValid( $tag, $expected = null )
	{
		$result = $this->object->validate($tag);

		$this->assertValidationResult($expected !== null ? $expected : $tag, $result);
	}

	static function getInvalidLanguageCodes()
	{
		return [
			[' '],
			['x'],
			['xx'],
			['x3e'],
			['x.y'],
			[' ger ']
		];
	}

	/**
	 * @dataProvider getInvalidLanguageCodes
	 */
	public function testInvalid( $tag )
	{
		$result = $this->object->validate($tag);

		$this->assertValidationErrorAndInfo('invalidLanguageCode', [], $result);
	}
}

