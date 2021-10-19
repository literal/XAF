<?php
namespace XAF\validate;

use PHPUnit\Framework\TestCase;
use Phake;

/**
 * Base class for all tests that evaluate ValidationResult objects
 */
class ValidationTestBase extends TestCase
{
	/** @var ValidationService */
	protected $validationServiceMock;

	/**
	 * @return ValidationService
	 */
	protected function getValidationService()
	{
		$this->validationServiceMock = \Phake::mock(ValidationService::class);
		return $this->validationServiceMock;
	}

	/**
	 * @param string $expectedValue
	 * @param ValidationResult $result
	 */
	protected function assertValidationResult( $expectedValue, ValidationResult $result )
	{
		$this->assertValidationPassed($result);
		$this->assertSame($expectedValue, $result->value);
	}

	protected function assertValidationPassed( ValidationResult $result )
	{
		$this->assertNull($result->errorKey, 'unexpected validation error: ');
	}

	/**
	 * @param string $expectedErrorKey
	 * @param string $expectedErrorInfo
	 * @param ValidationResult $result
	 */
	protected function assertValidationErrorAndInfo( $expectedErrorKey, $expectedErrorInfo, ValidationResult $result )
	{
		$this->assertValidationError($expectedErrorKey, $result);
		if( $expectedErrorInfo !== null )
		{
			$this->assertEquals($expectedErrorInfo, $result->errorInfo);
		}
	}

	/**
	 * @param string $expectedErrorKey
	 * @param ValidationResult $result
	 */
	protected function assertValidationError( $expectedErrorKey, ValidationResult $result )
	{
		$this->assertNull($result->value, 'error expected but result value not null: ');
		$this->assertEquals($expectedErrorKey, $result->errorKey);
	}

	protected function setValid( $value, $expression )
	{
		Phake::when($this->validationServiceMock)->validate($value, $expression)->thenReturn(
			ValidationResult::createValid($value)
		);
	}

	protected function setInvalid( $value, $expression, $message )
	{
		Phake::when($this->validationServiceMock)->validate($value, $expression)->thenReturn(
			ValidationResult::createError($message)
		);
	}
}
