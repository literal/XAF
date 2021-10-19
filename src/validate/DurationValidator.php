<?php
namespace XAF\validate;

/**
 * @errorKey invalidDuration
 */
class DurationValidator extends NotEmptyValidator
{
	public function validate( $value )
	{
		$result = parent::validate($value);
		if( $result->errorKey )
		{
			return $result;
		}

		$value = $result->value;

		if( !\is_int($value) )
		{
			if( !\preg_match('/^(\\d+):([0-5]\\d)$/', $value, $matches) )
			{
				return ValidationResult::createError('invalidDuration');
			}

			$minutes = \intval($matches[1], 10);
			$seconds = \intval($matches[2], 10);
			$value = $minutes * 60 + $seconds;
		}

		return ValidationResult::createValid($value);
	}
}
