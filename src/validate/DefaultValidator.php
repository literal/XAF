<?php
namespace XAF\validate;

/**
 * Return default value if input value is empty
 */
class DefaultValidator implements Validator
{
	public function validate( $value, $default = null )
	{
		return ValidationResult::createValid($value === null || $value === '' ? $default : $value);
	}
}
