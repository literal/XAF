<?php
namespace XAF\di;

interface Factory
{
	/**
	 * @return array
	 */
	public function getCreatableObjectAliases();

	/**
	 * @param string $key
	 * @return bool
	 */
	public function canCreateObject( $key );

	/**
	 * @param string $key
	 * @return object
	 */
	public function createObject( $key );
}
