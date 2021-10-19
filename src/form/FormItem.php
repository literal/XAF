<?php
namespace XAF\form;

use XAF\validate\ValidationService;

/**
 * Base for the Form structure, field collections and individual fields
 */
abstract class FormItem
{
	/** @var ValidationService */
	protected $validationService;

	/** @var string */
	protected $name;

	/** @var array */
	protected $schema = [];

	/** @var array */
	protected $params = [];

	/** @var string|null */
	protected $errorKey;

	/** @var array|null hash of details about the error */
	protected $errorInfo;

	public function __construct( ValidationService $validationService )
	{
		$this->validationService = $validationService;
	}

	/**
	 * @param string $name name as in the corresponding HTML input element's name attribute
	 */
	public function setName( $name )
	{
		$this->name = $name;
	}

	public function setSchema( $schema )
	{
		$this->schema = $schema;
	}

	/**
	 * Set a named multi-purpose parameter for use at output time,
	 * e. g. a hash of permitted keys/values for a selection field
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function setParam( $key, $value )
	{
		$this->params[$key] = $value;
	}

	/**
	 * Get named multi-purpose parameter
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getParam( $key )
	{
		return isset($this->params[$key]) ? $this->params[$key] : null;
	}

	/**
	 * @param mixed $value
	 * @return bool Whether the value (or a part of it) was actually used - relevant only for item
	 *     collections, where only those elements of $value are used, for which a field is defined
	 */
	abstract public function setValue( $value );

	/**
	 * @return mixed
	 */
	abstract public function getValue();

	abstract public function setDefault();

	/**
	 * @return bool true if no validation error occurred
	 */
	abstract public function validate();

	/**
	 * @return bool
	 */
	public function hasError()
	{
		return $this->errorKey !== null;
	}

	/**
	 * @return array {'key': <string|null>, 'info': <array|null>}
	 */
	public function getError()
	{
		return [
			'key' => $this->errorKey,
			'info' => $this->errorInfo
		];
	}

	/**
	 * @return string|null
	 */
	public function getErrorKey()
	{
		return $this->errorKey;
	}

	/**
	 * @return array|null
	 */
	public function getErrorInfo()
	{
		return $this->errorInfo;
	}

	/**
	 * @param string|null $errorKey
	 * @param array|null $errorInfo
	 */
	public function setError( $errorKey, $errorInfo = null )
	{
		$this->errorKey = $errorKey;
		$this->errorInfo = $errorInfo;
	}
}
