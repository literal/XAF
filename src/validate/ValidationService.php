<?php
namespace XAF\validate;

interface ValidationService
{
	/**
	 * @param mixed $value
	 * @param string $expression
	 * @return ValidationResult
	 */
	public function validate( $value, $expression );
}
