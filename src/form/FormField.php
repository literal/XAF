<?php
namespace XAF\form;

/**
 * Individual field of a Form object
 */
class FormField extends FormItem
{
	protected $value;

	/**
	 * @return string to be used for the 'name'-attribute on the HTML input or selct element
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string to be used for the 'id'-attribute on the HTML input or selct element
	 */
	public function getId()
	{
		$result = \strtr($this->getName(), '[]-.(){}', '____________');
		$result = \str_replace('__', '_', $result);
		return \trim($result, '_');
	}

	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	public function setValue( $value )
	{
		$this->value = $value;
		return true;
	}

	public function setDefault()
	{
		$this->setValue(isset($this->schema['default']) ? $this->schema['default'] : null);
	}

	/**
	 * @return bool
	 */
	public function validate()
	{
		if( empty($this->schema['rule']) )
		{
			return true;
		}

		$value = $this->getValue();
		if( \is_string($value) && (!isset($this->schema['trim']) || $this->schema['trim']) )
		{
			$value = \trim($value);
		}

		$validationResult = $this->validationService->validate($value, $this->schema['rule']);
		if( $validationResult->errorKey )
		{
			$this->setError($validationResult->errorKey, $validationResult->errorInfo);
			return false;
		}

		$this->setValue($validationResult->value);
		return true;
	}

	public function __toString()
	{
		return \strval($this->value);
	}
}
