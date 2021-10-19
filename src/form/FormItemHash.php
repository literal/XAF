<?php
namespace XAF\form;

use XAF\exception\SystemError;

/**
 * A collection of form items of the same type indexed by keys. Keys are dynamic, i. e. not defined by the schema
 * but part of the data.
 */
class FormItemHash extends FormItemCollection
{
	public function setSchema( $schema )
	{
		if( !isset($schema['hash']) )
		{
			throw new SystemError('element \'hash\' must be present for a form item hash');
		}
		$this->itemSchema = $schema['hash'];
		parent::setSchema($schema);
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function setItem( $key, $value )
	{
		$item = $this->getOrCreateItem($key);
		$item->setValue($value);
	}

	/**
	 * Set default item set keeping any existing items.
	 *
	 * Used to e.g. add unchecked checkbox form items
	 *
	 * @param array $template
	 */
	public function setTemplate( array $template )
	{
		$existingItems = $this->items;
		$this->setValue($template);
		$this->items = \array_replace($this->items, $existingItems);
	}
}

