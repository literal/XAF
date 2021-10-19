<?php
namespace XAF\validate;

interface Validator
{
	/**
	 * @param mixed $value
	 * @param mixed ...
	 * @return ValidationResult
	 */
	public function validate( $value );
}
