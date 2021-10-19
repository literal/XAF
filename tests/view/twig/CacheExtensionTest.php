<?php
namespace XAF\view\twig;

use TwigTestBase;
use Phake;

use Doctrine\Common\Cache\Cache as DoctrineCache;

require_once __DIR__ . '/TwigTestBase.php';

/**
 * @covers \XAF\view\twig\CacheExtension
 * @covers \XAF\view\twig\CacheTokenParser
 * @covers \XAF\view\twig\CacheNode
 * @covers \XAF\view\twig\DoctrineCacheProvider
 */
class CacheExtensionTest extends TwigTestBase
{
	/** @var DoctrineCache */
	private $cacheMock;

	protected function setUp(): void
	{
		parent::setUp();
		$this->cacheMock = Phake::mock(DoctrineCache::class);
		$cacheProvider = new DoctrineCacheProvider(($this->cacheMock));
		$cacheExtension = new CacheExtension($cacheProvider);
		$this->environment->addExtension($cacheExtension);
	}

	public function testCachableBlockIsRenderedIfNotPresentInCache()
	{
		$this->setupTemplate('template.twig', "{% cache 'blockKey' 123 %}block contents{% endcache %}");

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('block contents', $result);
	}

	public function testCachableBlockIsNotRenderedButLoadedFromCacheIfPresent()
	{
		$this->setupTemplate('template.twig', "{% cache 'blockKey' 123 %}block contents{% endcache %}");
		Phake::when($this->cacheMock)->fetch('XAFcache:blockKey')->thenReturn('cached contents');

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('cached contents', $result);
	}

	public function testRenderedBlockIsStoredInCache()
	{
		$this->setupTemplate('template.twig', "{% cache 'blockKey' 123 %}block contents{% endcache %}");

		$this->renderTemplate('template.twig');

		Phake::verify($this->cacheMock)->save('XAFcache:blockKey', 'block contents', 123);
	}

	public function testCacheKeyAndLifetimeCanBeExpressions()
	{
		$this->setupTemplate('template.twig', "{% cache 'block' ~ 'Key' 11 * 2 %}block contents{% endcache %}");

		$this->renderTemplate('template.twig');

		Phake::verify($this->cacheMock)->save('XAFcache:blockKey', 'block contents', 22);
	}
}
