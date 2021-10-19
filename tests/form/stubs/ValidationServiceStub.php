<?php
namespace XAF\validate;


class ValidationServiceStub implements ValidationService
{
	public $callCount = 0;

	/**
	 * @param mixed $value
	 * @param string $expression
	 * @return ValidationResult
	 */
	public function validate( $value, $expression )
	{
		$this->callCount++;

		if( $expression == 'pass' )
		{
			return ValidationResult::createValid($value);
		}

		if( $expression == 'fail' )
		{
			return ValidationResult::createError('failure');
		}
	}
}
