<?php
namespace XAF\validate;

/**
 * @errorKey empty
 * @errorKey tooShort(min, actual)
 * @errorKey tooLong(max, actual)
 */
class StringValidator implements Validator
{
	public function validate( $value, $minLength = 1, $maxLength = null )
	{
		// remove Windows linebreak characters
		$value = \str_replace("\r", '', \strval($value));

		// Remove invalid UTF-8 sequences
		$value = \iconv('UTF-8', 'UTF-8//IGNORE', $value);

		$actualLength = \mb_strlen($value, 'UTF-8');
		if( $actualLength < $minLength )
		{
			return $actualLength > 0
				? ValidationResult::createError('tooShort', ['min' => $minLength, 'actual' => $actualLength])
				: ValidationResult::createError('empty');
		}
		if( $maxLength !== null && $actualLength > $maxLength )
		{
			return ValidationResult::createError('tooLong', ['max' => $maxLength, 'actual' => $actualLength]);
		}

		return ValidationResult::createValid($value);
	}
}
