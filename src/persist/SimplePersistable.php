<?php
namespace XAF\persist;

/**
 * Basic implementation of interface Persistable as an abstract base class
 *
 * Exports/imports all accessible properties, i. e. public and protected
 * properties of derived classes' objects.
 *
 * If you derive class, make sure all properties that are not to be
 * persisted are declared private!
 */
abstract class SimplePersistable implements Persistable
{
	public function importState( array $data )
	{
		foreach( $this as $k => $v )
		{
			if( \array_key_exists($k, $data) )
			{
				$this->$k = $data[$k];
			}
		}
	}

	public function exportState()
	{
		$data = [];
		foreach( $this as $k => $v )
		{
			$data[$k] = $v;
		}
		return $data;
	}
}

