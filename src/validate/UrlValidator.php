<?php
namespace XAF\validate;

/**
 * @errorKey invalidUrl
 *
 * This is a quick and loose validation
 * - It does not actually guarantee a valid URL
 * - It only cares for typical URLs navigable for a Browser (http, https and ftp)
 */
class UrlValidator extends NotEmptyValidator
{
	public function validate( $value )
	{
		$result = parent::validate($value);
		if( $result->errorKey )
		{
			return $result;
		}
		$value = $result->value;

		if( !\preg_match('#^(?:http|https|ftp)://.+/#u', $value) )
		{
			return ValidationResult::createError('invalidUrl');
		}

		return ValidationResult::createValid($value);
	}
}

