<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\UrlPathValidator
 */
class UrlPathValidatorTest extends ValidationTestBase
{
	/** @var UrlPathValidator */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new UrlPathValidator;
	}

	public function testEmpty()
	{
		$result = $this->object->validate('');

		$this->assertValidationErrorAndInfo('empty', [], $result);
	}

	static function getValidUrlPaths()
	{
		return [
			['/foo'],
			['bar\\boom:quux'],
			['/'],
			['htt\\p:foo'], // Not considered a scheme because  not allowed in scheme name according to RFC 3986
			['äöüß€ć'], // May contain Unicode
		];
	}

	/**
	 * @dataProvider getValidUrlPaths
	 */
	public function testValid( $url )
	{
		$result = $this->object->validate($url);

		$this->assertValidationResult($url, $result);
	}

	static function getInvalidUrlPaths()
	{
		return [
			['foo://www.google.com/'],
			['HTTP:quux'],
			['a.b+c-d:quux'], // Begins with scheme (scheme names may contain .-+ according to RFC 3986)
			["xxx\0x00yyy"],  // Contains control character
			["xxx\0x82yyy"],  // Contains high control character
		];
	}

	/**
	 * @dataProvider getInvalidUrlPaths
	 */
	public function testInvalid( $url )
	{
		$result = $this->object->validate($url);

		$this->assertValidationErrorAndInfo('invalidUrlPath', [], $result);
	}
}
