<?php
namespace XAF\config;

use XAF\type\ParamHolder;
use XAF\exception\SystemError;

/**
 * Provides access to configuration options
 *
 * Keys are hierarchical in dot-separated notation, e.g. 'main.sub.key' - or null for root
 */
interface Config extends ParamHolder
{
	/**
	 * Export a sub-tree of the configuration
	 *
	 * @param mixed $key
	 * @return ParamHolder
	 * @throws SystemError if key not found or value is not an array
	 */
	public function export( $key );
}
