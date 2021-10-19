<?php
namespace XAF\validate;

/**
 * @errorKey noNumber
 * @errorKey tooSmall(min)
 * @errorKey tooBig(max)
 */
class NumberValidator extends NotEmptyValidator
{
	public function validate( $value, $min = null, $max = null )
	{
		$result = parent::validate($value);
		if( $result->errorKey )
		{
			return $result;
		}
		$value = $result->value;

		if( !\is_int($value) && !\is_float($value) )
		{
			$value = \str_replace(',', '.', $value);

			// character check excludes stuff like '22e5', '0x0A' etc that is_numeric accepts
			if( !\is_numeric($value) || \strspn($value, '-0123456789.') < \strlen($value) )
			{
				return ValidationResult::createError('noNumber');
			}

			$value = \floatval($value);
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
