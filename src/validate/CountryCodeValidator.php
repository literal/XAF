<?php

namespace XAF\validate;

/**
 * For ISO two upper letter country codes, e.g. 'DE' or 'US'
 *
 * @errorKey invalidCountryCode
 */
class CountryCodeValidator extends NotEmptyValidator
{
	public function validate( $value )
	{
		$result = parent::validate($value);
		if( $result->errorKey )
		{
			return $result;
		}
		$value = $result->value;

		if( !\preg_match('/^[A-Z]{2}$/i', $value) )
		{
			return ValidationResult::createError('invalidCountryCode');
		}

		$value = \strtoupper($value);

		return ValidationResult::createValid($value);
	}
}
