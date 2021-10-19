<?php
namespace XAF\validate;

/**
 * Language independent ISO/DIN/SQL date validator
 *
 * @errorKey invalidDateFormat(expected)
 * @errorKey invalidDate
 */
class PartialDateValidator extends NotEmptyValidator
{
	public function validate( $value )
	{
		$result = parent::validate($value);
		if( $result->errorKey )
		{
			return $result;
		}
		$value = $result->value;

		if( !\preg_match('/^([0-9]{4})(?:-([0-9]{1,2}))?(?:-([0-9]{1,2}))?$/', $value, $matches) )
		{
			return ValidationResult::createError('invalidDateFormat', ['expected' => 'YYYY-MM-DD']);
		}

		$year = \intval($matches[1], 10);
		$month = isset($matches[2]) ? \intval($matches[2], 10) : null;
		$day = isset($matches[3]) ? \intval($matches[3], 10) : null;

		if( !\checkdate($month ?: 1, $day ?: 1, $year) )
		{
			return ValidationResult::createError('invalidDate');
		}

		$value = $year . ($month ? \sprintf('-%02d', $month) : '') . ($day ? \sprintf('-%02d', $day) : '');
		return ValidationResult::createValid($value);
	}
}
