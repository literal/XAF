<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\UrlValidator
 */
class UrlValidatorTest extends ValidationTestBase
{
	/** @var UrlValidator */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new UrlValidator;
	}

	public function testEmpty()
	{
		$result = $this->object->validate('');

		$this->assertValidationErrorAndInfo('empty', [], $result);
	}

	static function getValidUrls()
	{
		return [
			['http://www.google.com/'],
			['https://www.google.com/'],
			['ftp://www.google.com/'],
			['http://www.google.com/boom?zoom=foop#baz'],
			['http://localhost/'],
			['http://127.0.0.1/']
		];
	}

	/**
	 * @dataProvider getValidUrls
	 */
	public function testValid( $url )
	{
		$result = $this->object->validate($url);

		$this->assertValidationResult($url, $result);
	}

	static function getInvalidUrls()
	{
		return [
			['//www.google.com/'],		// no protocol
			['foo://www.google.com/'], // unknown/unsupported protocol
			['http:www.google.com/'],	// no double-slash after protocol
			['http://www.google.com']	// no slash after host
		];
	}

	/**
	 * @dataProvider getInvalidUrls
	 */
	public function testInvalid( $url )
	{
		$result = $this->object->validate($url);

		$this->assertValidationErrorAndInfo('invalidUrl', [], $result);
	}
}

