<?php
namespace XAF\view\twig;

use Doctrine\Common\Cache\Cache;

/**
 * Based on twig-cache-extension (c) 2013 Alexander <iam.asm89@gmail.com>
 * @link https://github.com/asm89/twig-cache-extension
 * @license https://github.com/asm89/twig-cache-extension/blob/master/LICENSE
 */
class DoctrineCacheProvider implements CacheProvider
{
	/** @var Cache */
	private $cache;

	public function __construct( Cache $cache )
	{
		$this->cache = $cache;
	}

	/**
	 * @param string $key
	 * @return string|null
	 */
	public function fetch( $key )
	{
		$result = $this->cache->fetch($key);
		return $result !== false ? $result : null;
	}

	/**
	 * @param string $key
	 * @param string $block
	 * @param int $lifetimeSeconds 0 means unlimited
	 */
	public function store( $key, $value, $lifetimeSeconds = 0 )
	{
		$this->cache->save($key, $value, $lifetimeSeconds);
	}
}
