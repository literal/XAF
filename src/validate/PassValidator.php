<?php
namespace XAF\validate;

/**
 * Default validator that will happily accept any value
 */
class PassValidator implements Validator
{
	public function validate( $value )
	{
		return ValidationResult::createValid($value);
	}
}
