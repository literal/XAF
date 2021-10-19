<?php
namespace XAF\validate;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\validate\ValidationResult
 */
class ValidationResultTest extends TestCase
{
	public function testCreateValidationError()
	{
		$result = ValidationResult::createError('errorKey', ['infoKey' => 'value']);

		$this->assertEquals('errorKey', $result->errorKey);
		$this->assertEquals(['infoKey' => 'value'], $result->errorInfo);
	}

	public function testCreateValidResult()
	{
		$result = ValidationResult::createValid('value');

		$this->assertEquals('value', $result->value);
		$this->assertNull($result->errorKey);
	}
}
