<?php
namespace XAF\form;

use XAF\exception\SystemError;

/**
 * A dynamic collection of form items of the same type.
 */
abstract class FormItemCollection extends FormItemAggregate
{
	/** @var mixed */
	protected $itemSchema;

	/**
	 * @param array $value
	 * @return bool
	 */
	public function setValue( $value )
	{
		if( !\is_array($value) || empty($value) )
		{
			$this->items = [];
			return true;
		}

		$this->createAndSetItems($value);
		return true;
	}

	public function setDefault()
	{
		if( !isset($this->schema['default']) )
		{
			return;
		}

		if( !\is_array($this->schema['default']) )
		{
			throw new SystemError('the default for a form item collection must be an array', $this->schema['default']);
		}

		$this->createAndSetItems($this->schema['default']);
	}

	/**
	 * @param array $values
	 */
	protected function createAndSetItems( array $values )
	{
		$this->items = [];
		foreach( $values as $key => $value )
		{
			$item = $this->getOrCreateItem($key);
			$item->setValue($value);
		}
	}

	/**
	 * @param mixed $key
	 * @return FormItem
	 */
	protected function getOrCreateItem( $key )
	{
		if( !isset($this->items[$key]) )
		{
			$item = $this->createItem($this->getItemName($key), $this->itemSchema);
			$this->items[$key] = $item;
		}
		return $this->items[$key];
	}
}
