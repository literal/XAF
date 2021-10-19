<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\DomainNameValidator
 */
class DomainNameValidatorTest extends ValidationTestBase
{
	/** @var UrlValidator */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new DomainNameValidator;
	}

	public function testEmpty()
	{
		$result = $this->object->validate('');

		$this->assertValidationErrorAndInfo('empty', [], $result);
	}

	static function getValidDomains()
	{
		return [
			['www.google.com'],
			['www.google.123.com'],
			['www.g-oogle.com']
		];
	}

	/**
	 * @dataProvider getValidDomains
	 */
	public function testValid( $domain )
	{
		$result = $this->object->validate($domain);

		$this->assertValidationResult($domain, $result);
	}

	static function getInvalidDomains()
	{
		return [
			['.www.google.com'],
			['www.google.com.'],
			['www.-google.com'],
			['www.google-.com'],
			['ww-.google.-om'],
			['www.google.com/'],
			['http://www.google.com'],
			['www.google.com/'],
			['www.google.com/foo'],
			['www.' . \str_repeat('A', 64) . '.com'],
			['www.' . \str_repeat('A', 256) . '.com']
		];
	}

	/**
	 * @dataProvider getInvalidDomains
	 */
	public function testInvalid( $domain )
	{
		$result = $this->object->validate($domain);

		$this->assertValidationErrorAndInfo('invalidDomain', [], $result);
	}
}
