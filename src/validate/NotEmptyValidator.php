<?php
namespace XAF\validate;

/**
 * @errorKey empty
 */
class NotEmptyValidator implements Validator
{
	public function validate( $value )
	{
		if( $value === '' || $value === null || $value === [] )
		{
			return ValidationResult::createError('empty');
		}

		return ValidationResult::createValid($value);
	}
}
