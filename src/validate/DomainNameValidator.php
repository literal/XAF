<?php
namespace XAF\validate;

/**
 * @errorKey invalidDomain
 */
class DomainNameValidator extends NotEmptyValidator
{
	public function validate( $value )
	{
		$result = parent::validate($value);
		if( $result->errorKey )
		{
			return $result;
		}
		$value = $result->value;

		// the domain name has to be shorter than 256 characters
		// domain name may contain 2 or more labels divided by dots '.'
		// each label hast to contain between 1 and 64 characters
		// each label has to consist of alphanumeric characters or a dash '-'
		// the dash may not be the first or the last character of a label
		$labelExpression = '(?:[a-z0-9][-a-z0-9]{0,61}[a-z0-9]|[a-z0-9])';
		$pattern = '/^' . $labelExpression . '(\\.' . $labelExpression . ')+$/i';
		if( \strlen($value) > 256 || !\preg_match($pattern, $value) )
		{
			return ValidationResult::createError('invalidDomain');
		}

		return ValidationResult::createValid($value);
	}
}
