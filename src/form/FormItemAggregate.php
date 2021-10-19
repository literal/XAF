<?php
namespace XAF\form;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;

use XAF\exception\SystemError;

/**
 * Aggregate of multiple for items being treated like a single item
 */
abstract class FormItemAggregate extends FormItem implements ArrayAccess, Countable, IteratorAggregate
{
	/** @var FormItem[] */
	protected $items = [];

	/**
	 * @return array
	 */
	public function getValue()
	{
		$result = [];
		foreach( $this->items as $key => $item ) /* @var $item FormItem */
		{
			$result[$key] = $item->getValue();
		}
		return $result;
	}

	/**
	 * @return bool
	 */
	public function validate()
	{
		$allValid = true;
		foreach( $this->items as $item )
		{
			$allValid = $item->validate() && $allValid;
		}
		return $allValid;
	}

	/**
	 * @param string $itemKey
	 * @return string
	 */
	protected function getItemName( $itemKey )
	{
		return isset($this->name)
			? $this->name . '[' . $itemKey . ']'
			: $itemKey;
	}

	/**
	 * @param string $itemName
	 * @param array|string $schema
	 * @return FormItem
	 */
	protected function createItem( $itemName, $schema )
	{
		$schema = $this->normalizeItemSchema($schema);
		switch( true )
		{
			case isset($schema['struct']):
				$item = new FormItemStruct($this->validationService);
				break;

			case isset($schema['array']):
				$item = new FormItemArray($this->validationService);
				break;

			case isset($schema['hash']):
				$item = new FormItemHash($this->validationService);
				break;

			default:
				$item = new FormField($this->validationService);
				break;
		}
		$item->setName($itemName);
		$item->setSchema($schema);
		return $item;
	}

	protected function normalizeItemSchema( $schema )
	{
		return \is_array($schema) ? $schema : ['rule' => $schema];
	}

	/**
	 * @return bool
	 */
	public function hasError()
	{
		if( parent::hasError() )
		{
			return true;
		}

		foreach( $this->items as $item )
		{
			if( $item->hasError() )
			{
				return true;
			}
		}

		return false;
	}

	// ************************************************************************
	// Implementation of ArrayAccess
	// ************************************************************************

	public function offsetExists( $offset )
	{
		return \array_key_exists($offset, $this->items);
	}

	public function offsetGet( $offset )
	{
		if( !$this->offsetExists($offset) )
		{
			throw new SystemError('undefined form item', $offset);
		}
		return $this->items[$offset];
	}

	public function offsetSet( $offset, $value )
	{
		throw new SystemError('form items cannot be set', $offset, 'try setValue() on the item instead');
	}

	public function offsetUnset( $offset )
	{
		throw new SystemError('form items cannot be unset', $offset);
	}

	// ************************************************************************
	// Implementation of Countable
	// ************************************************************************

	public function count()
	{
		return \count($this->items);
	}

	// ************************************************************************
	// Implementation of IteratorAggregate
	// ************************************************************************

	public function getIterator()
	{
		return new ArrayIterator($this->items);
	}
}
