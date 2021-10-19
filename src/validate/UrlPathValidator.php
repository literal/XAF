<?php
namespace XAF\validate;

/**
 * @errorKey invalidUrlPath
 */
class UrlPathValidator extends NotEmptyValidator
{
	public function validate( $value )
	{
		$result = parent::validate($value);
		if( $result->errorKey )
		{
			return $result;
		}
		$value = $result->value;

		// Must not start with scheme (RFC 3986)
		if( \preg_match('/^[a-z][a-z0-9\\.+\\-]*:/i', $value) )
		{
			return ValidationResult::createError('invalidUrlPath');
		}

		// Must not contain control characters
		if( \preg_match('/[\\x00-\\x1f\\x7f-\\x9f]/u', $value) )
		{
			return ValidationResult::createError('invalidUrlPath');
		}

		return ValidationResult::createValid($value);
	}
}
