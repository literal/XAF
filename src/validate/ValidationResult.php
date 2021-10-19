<?php
namespace XAF\validate;

/**
 * Public data structure returned by validation methods
 */
class ValidationResult
{
	/**
	 * @var mixed the sanitized/normalizted/converted value if validation successful
	 */
	public $value;

	/**
	 * @var string key for the type of error, e.g. 'tooShort' or 'noInt'
	 *     If validation is successful, the value remains null
	 */
	public $errorKey = null;

	/**
	 * @var array hashmap of detail information about why the validation failed (if applicable),
	 *     e.g. {'min': 4, 'actual': 1} for a 'tooShort' error
	 */
	public $errorInfo = [];

	/**
	 * Factory method for creating an instance that is a result of a failed validation
	 *
	 * @param string $errorKey
	 * @param array $errorInfo
	 * @return ValidationResult
	 */
	static public function createError( $errorKey, array $errorInfo = [] )
	{
		$instance = new self();
		$instance->errorKey = $errorKey;
		$instance->errorInfo = $errorInfo;
		return $instance;
	}

	/**
	 * Factory method for creating an instance that is a result of a successful validation
	 *
	 * @param mixed $value
	 * @return ValidationResult
	 */
	static public function createValid( $value )
	{
		$instance = new self();
		$instance->value = $value;
		return $instance;
	}
}
