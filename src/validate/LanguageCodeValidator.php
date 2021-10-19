<?php
namespace XAF\validate;

/**
 * For ISO 369-2 three-letter language codes, e.g. 'ger'.
 *
 * @errorKey invalidLanguageCode
 */
class LanguageCodeValidator extends NotEmptyValidator
{
	public function validate( $value )
	{
		$result = parent::validate($value);
		if( $result->errorKey )
		{
			return $result;
		}
		$value = $result->value;

		if( !\preg_match('/^[A-Za-z]{3}$/', $value) )
		{
			return ValidationResult::createError('invalidLanguageCode');
		}

		$value = \strtolower($value);

		return ValidationResult::createValid($value);
	}
}
