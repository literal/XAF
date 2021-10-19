<?php
namespace XAF\validate;

use XAF\exception\SystemError;

/**
 * Validate password length and complexity
 *
 * @errorKey passwordTooSimple
 */
class PasswordValidator extends StringValidator
{
	const COMPLEXITY_ANY = 'any';
	const COMPLEXITY_DEFAULT = 'default';

	/**
	 * @param string $value
	 * @param int $minLength
	 * @param string $complexityRule Any of the COMPLEXITY_* constants
	 * @param int|null $maxLength
	 * @return ValidationResult
	 */
	public function validate(
		$value,
		$minLength = 6,
		$complexityRule = self::COMPLEXITY_DEFAULT,
		$maxLength = null
	)
	{
		$result = parent::validate($value, $minLength, $maxLength);
		if( $result->errorKey !== null )
		{
			return $result;
		}

		if( !$this->isComplexEnough($value, $complexityRule) )
		{
			return ValidationResult::createError('passwordTooSimple');
		}
		return ValidationResult::createValid($value);
	}

	/**
	 * @param string $password
	 * @param string $complexityRule Any of the COMPLEXITY_* constants
	 * @return boolean
	 */
	private function isComplexEnough( $password, $complexityRule )
	{
		switch( $complexityRule )
		{
			case self::COMPLEXITY_ANY:
				return true;

			case self::COMPLEXITY_DEFAULT:
				return
					$this->containsLowercaseLetter($password) &&
					$this->containsUppercaseLetter($password) &&
					$this->containsNonLetter($password);
		}
		throw new SystemError('unknown password complexity rule token', $complexityRule);
	}

	/**
	 * @param string $password
	 * @return bool
	 */
	private function containsUppercaseLetter( $password )
	{
		return \preg_match('/[A-Z]/', $password);
	}

	/**
	 * @param string $password
	 * @return bool
	 */
	private function containsLowercaseLetter( $password )
	{
		return \preg_match('/[a-z]/', $password);
	}

	/**
	 * @param string $password
	 * @return bool
	 */
	private function containsNonLetter( $password )
	{
		return \preg_match('/[^a-zA-Z]/', $password);
	}
}
