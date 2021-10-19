<?php
namespace XAF\view\twig;

/**
 * Based on twig-cache-extension (c) 2013 Alexander <iam.asm89@gmail.com>
 * @link https://github.com/asm89/twig-cache-extension
 * @license https://github.com/asm89/twig-cache-extension/blob/master/LICENSE
 */
interface CacheProvider
{
	/**
	 * @param string $key
	 * @return string|null
	 */
	public function fetch( $key );

	/**
	 * @param string $key
	 * @param string $value
	 * @param int $lifetimeSeconds 0 means unlimited
	 */
	public function store( $key, $value, $lifetimeSeconds );
}
