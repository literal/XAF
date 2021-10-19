<?php
namespace XAF\view\twig;

use Twig_Extension as Extension;

/**
 * Provides caching for rendering results of sections of templates.
 *
 * Usage:
 *   {% cache <unique block key> <lifetime in seconds> %}
 *       {# content here will be fetched from cache next time #}
 *   {% endcache %}
 *
 * Based on twig-cache-extension (c) 2013 Alexander <iam.asm89@gmail.com>
 * @link https://github.com/asm89/twig-cache-extension
 * @license https://github.com/asm89/twig-cache-extension/blob/master/LICENSE
 */
class CacheExtension extends Extension
{
	/** @var CacheProvider */
	private $cacheProvider;

	private $cacheKeyPrefix;

	/**
	 * @param CacheProvider $cacheProvider
	 * @param string $cacheKeyPrefix Prepended before every block key when storing in/loading from the external
	 *	 cache provider (to avoid key collisions, because the actual cache may be shared with other users)
	 */
	public function __construct( CacheProvider $cacheProvider, $cacheKeyPrefix = 'XAFcache:' )
	{
		$this->cacheProvider = $cacheProvider;
		$this->cacheKeyPrefix = $cacheKeyPrefix;
	}

	/**
	 * @param string $blockKey
	 * @return string|null
	 */
	public function fetchBlock( $blockKey )
	{
		$cacheKey = $this->buildCacheKey($blockKey);
		return $this->cacheProvider->fetch($cacheKey);
	}

	/**
	 * @param string $blockKey
	 * @param string $blockContents
	 * @param int $lifetimeSeconds 0 means unlimited
	 */
	public function storeBlock( $blockKey, $blockContents, $lifetimeSeconds = 0 )
	{
		$cacheKey = $this->buildCacheKey($blockKey);
		$this->cacheProvider->store($cacheKey, $blockContents, $lifetimeSeconds);
	}

	/**
	 * @param string $blockKey
	 * @return string
	 */
	private function buildCacheKey( $blockKey )
	{
		return $this->cacheKeyPrefix . $blockKey;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'XAFcache';
	}

	/**
	 * @return [TokenParserInterface]
	 */
	public function getTokenParsers()
	{
		return [new CacheTokenParser()];
	}
}
