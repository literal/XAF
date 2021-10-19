<?php
namespace XAF\validate;

/**
 * @errorKey notEmpty
 */
class EmptyValidator implements Validator
{
	public function validate( $value )
	{
		if( $value !== '' && $value !== null && $value !== [] )
		{
			return ValidationResult::createError('notEmpty');
		}

		return ValidationResult::createValid(null);
	}
}
