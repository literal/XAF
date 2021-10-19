<?php
namespace XAF\form;

use XAF\validate\ValidationService;

/**
 * A concrete form object used in the application can either be of this class (thee $schema constructor argument or
 * a call to setSchema() would be employed to configure the fields) or of a derived class which can perform custom
 * initialisation, validation etc.
 */
class DefaultForm extends FormItemStruct implements Form
{
	/** @var bool */
	protected $wasReceived = false;

	/**
	 * @param ValidationService $validationService
	 * @param array $schema
	 */
	public function __construct( ValidationService $validationService, $schema = null )
	{
		parent::__construct($validationService);
		if( $schema !== null )
		{
			$this->setSchema($schema);
		}
	}

	/**
	 * @param array $schema
	 */
	public function setSchema( $schema )
	{
		parent::setSchema(['struct' => $schema]);
	}

	public function populateWithDefaults()
	{
		$this->setDefault();
	}

	/**
	 * @param array $values
	 * @return bool
	 */
	public function importValues( array $values )
	{
		return $this->setValue($values);
	}

	// Needs to be implemented in this class to satisfy interface
	// Maybe a PHP bug having to do with the fact that this method is also defined as abstract in a base class
	public function validate()
	{
		return parent::validate();
	}

	/**
	 * @return array
	 */
	public function exportValues()
	{
		return $this->getValue();
	}

	public function setReceived()
	{
		$this->wasReceived = true;
	}

	/**
	 * @return bool
	 */
	public function wasReceived()
	{
		return $this->wasReceived;
	}

	/**
	 * @param string|null $errorKey
	 * @param array|null $errorInfo
	 */
	public function setGlobalError( $errorKey, $errorInfo = null )
	{
		$this->setError($errorKey, $errorInfo);
	}

	/**
	 * @return bool
	 */
	public function hasGlobalError()
	{
		return $this->errorKey !== null;
	}
}
