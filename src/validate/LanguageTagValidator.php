<?php
namespace XAF\validate;

/**
 * For language tags, consisting of one ore more grups of letters or numbers separated by dashes.
 * The tags may provide information beyond the actual language, e.g. a culture/territory which can determine
 * how numbers, dates etc. are formatted, a script type and so on.
 *
 * Examples 'de' (German), 'de-at' (German as used in Austria), 'zh-hans' (Chinese, written in simplified script)
 *
 * @errorKey invalidLanguageTag
 */
class LanguageTagValidator extends NotEmptyValidator
{
	public function validate( $value )
	{
		$result = parent::validate($value);
		if( $result->errorKey )
		{
			return $result;
		}
		$value = $result->value;

		if( !\preg_match('/^[A-Za-z]{2,3}(?:[_-][a-zA-Z0-9]+)*$/', $value) )
		{
			return ValidationResult::createError('invalidLanguageTag');
		}

		$value = \strtr(\strtolower($value), '_', '-');

		return ValidationResult::createValid($value);
	}
}
