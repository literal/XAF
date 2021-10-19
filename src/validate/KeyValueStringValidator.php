<?php
namespace XAF\validate;

use XAF\helper\KeyValueStringHelper;

/**
 * @errorKey badKeyValueString
 */
class KeyValueStringValidator implements Validator
{
	public function validate( $value )
	{
		$hash = KeyValueStringHelper::decode($value);

		// Most basic validation here only: fail if the decoded result is empty but the original string is not
		// (except for whitespace). Any mixture of garbage and valid fields is accepted, though (and the garbage
		// silently discarded).
		if( !$hash && \trim($value) !== '' )
		{
			return ValidationResult::createError('badKeyValueString');
		}

		return ValidationResult::createValid(KeyValueStringHelper::encode($hash));
	}
}
