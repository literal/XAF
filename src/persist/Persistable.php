<?php
namespace XAF\persist;

/**
 * This interface will normally be implemented to store object state in a web-app session, not for an ORM.
 *
 * An object of an implementing class can choose to export any data as an arbitrary structure of
 * array/hash and scalar values (no objects and resource handles!) and can expect to get that same
 * structure injected to restore it's state after instatiation.
 */
interface Persistable
{
	/**
	 * @param array $data
	 */
	public function importState( array $data );

	/**
	 * @return array
	 */
	public function exportState();
}
