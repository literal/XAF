<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\LanguageTagValidator
 */
class LanguageTagValidatorTest extends ValidationTestBase
{
	/**
	 * @var LanguageTagValidator
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new LanguageTagValidator;
	}

	public function testEmpty()
	{
		$result = $this->object->validate('');

		$this->assertValidationErrorAndInfo('empty', [], $result);
	}

	static function getValidLanguageTags()
	{
		return [
			['de'],
			['DE', 'de'],
			['dua'],
			['de_DE', 'de-de'],
			['en-us'],
			['es-419'],					// latin american spanish
			['zh_Hans_HK', 'zh-hans-hk']	// chinese, simplified script, Hongkong
		];
	}

	/**
	 * @dataProvider getValidLanguageTags
	 */
	public function testValid( $tag, $expected = null )
	{
		$result = $this->object->validate($tag);

		$this->assertValidationResult($expected !== null ? $expected : $tag, $result);
	}

	static function getInvalidLanguageTags()
	{
		return [
			[' '],
			['x'],
			['12'],
			['xxxx'],
			['de.DE'],
			[' de_DE '],
			['de_'],
			['_de']
		];
	}

	/**
	 * @dataProvider getInvalidLanguageTags
	 */
	public function testInvalid( $tag )
	{
		$result = $this->object->validate($tag);

		$this->assertValidationErrorAndInfo('invalidLanguageTag', [], $result);
	}
}

