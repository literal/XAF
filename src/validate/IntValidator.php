<?php
namespace XAF\validate;

/**
 * @errorKey noInt
 * @errorKey tooSmall(min)
 * @errorKey tooBig(max)
 */
class IntValidator extends NotEmptyValidator
{
	public function validate( $value, $min = null, $max = null )
	{
		$result = parent::validate($value);
		if( $result->errorKey )
		{
			return $result;
		}

		$value = $result->value;

		if( !\is_int($value) )
		{
			if( !\preg_match('/^-?[0123456789]+$/', $value) )
			{
				return ValidationResult::createError('noInt');
			}
			$value = \intval($value, 10);
		}

		if( $min !== null && $value < $min )
		{
			return ValidationResult::createError('tooSmall', ['min' => $min]);
		}

		if( $max !== null && $value > $max )
		{
			return ValidationResult::createError('tooBig', ['max' => $max]);
		}

		return ValidationResult::createValid($value);
	}
}
