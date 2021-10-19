<?php
namespace XAF\form;

use XAF\exception\SystemError;

/**
 * Scalar array of form items of the same type.
 */
class FormItemArray extends FormItemCollection
{
	/** @var mixed */
	protected $itemSchema;

	public function setSchema( $schema )
	{
		if( !isset($schema['array']) )
		{
			throw new SystemError('schema element \'array\' must be present for a form item array');
		}
		$this->itemSchema = $schema['array'];
		parent::setSchema($schema);
	}

	/**
	 * @param mixed $value
	 * @return int
	 */
	public function addItem( $value )
	{
		$key = \count($this->items);
		$item = $this->getOrCreateItem($key);
		$item->setValue($value);
		return $key;
	}

	/**
	 * @param array $values
	 */
	protected function createAndSetItems( array $values )
	{
		parent::createAndSetItems(\array_values($values));
	}
}

