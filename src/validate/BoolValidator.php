<?php
namespace XAF\validate;

class BoolValidator implements Validator
{
	public function validate( $value )
	{
		$value = $value !== null && $value !== false &&
			!\in_array(\strtolower($value), ['', '0', 'no', 'off', 'false'], true);

		return ValidationResult::createValid($value);
	}
}

