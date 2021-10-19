<?php
namespace XAF\form;

use XAF\exception\SystemError;

/**
 * A collection of form items indexed by keys. The contained items can be of different types. Their keys and
 * schema are defined in the schema-element 'struct'.
 */
class FormItemStruct extends FormItemAggregate
{
	public function setSchema( $schema )
	{
		if( !isset($schema['struct']) )
		{
			throw new SystemError('element \'struct\' must be present for a form item struct');
		}
		parent::setSchema($schema);
		$this->createAllItems($this->schema['struct']);
	}

	protected function createAllItems( $itemSchemaMap )
	{
		foreach( $itemSchemaMap as $key => $itemSchema )
		{
			$this->items[$key] = $this->createItem($this->getItemName($key), $itemSchema);
		}
	}

	/**
	 * @param array $value
	 * @return bool
	 */
	public function setValue( $value )
	{
		if( empty($value) || !\is_array($value) )
		{
			return false;
		}

		$valueUsed = false;
		foreach( $this->items as $key => $item ) /* @var $item FormItem */
		{
			if( isset($value[$key]) )
			{
				$item->setValue($value[$key]);
				$valueUsed = true;
			}
		}
		return $valueUsed;
	}

	public function setDefault()
	{
		foreach( $this->items as $item )
		{
			$item->setDefault();
		}
	}
}
