<?php
namespace XAF\validate;

/**
 * @errorKey invalidEmail
 */
/**
 * This is a very loose implementation of RFC 2822. I.e. it will let some more obscure
 * cases of invalid addresses pass for the benefit of keeping the validation simple.
 */
class EmailValidator extends NotEmptyValidator
{
	public function validate( $value )
	{
		$result = parent::validate($value);
		if( $result->errorKey )
		{
			return $result;
		}
		$value = $result->value;

		// RFC 2822 says the local part may even contain @ if escaped by a backslash
		// or spaces if local part is enclosed in quotation marks.
		// So we just accept any non-empty ASCII string for a local part.
		// The domain part may contain special chars which would have to be punycode
		// encoded by the mailer and not here (image an address entered by a user, stored
		// in a DB and later presented to the user for editing - you want the original
		// special chars to still be there)
		if( !\preg_match('/^[\\x01-\\x7F]+@[^@\\s]+\\.[^@\\.\\s]+$/iu', $value) )
		{
			return ValidationResult::createError('invalidEmail');
		}

		return ValidationResult::createValid($value);
	}
}
