<?php
namespace XAF\web\routing;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\web\routing\PathPatternMatcher
 */
class PathPatternMatcherTest extends TestCase
{
	/**
	 * @var PathPatternMatcher
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new PathPatternMatcher();
	}

	public function testSimpleMatch()
	{
		$isMatching = $this->object->matchPath('foo', 'foo');

		$this->assertTrue($isMatching);
	}

	public function testSimpleMiss()
	{
		$isMatching = $this->object->matchPath('bar', 'foo');

		$this->assertFalse($isMatching);
	}

	public function testBeginningOfPathMatch()
	{
		$isMatching = $this->object->matchPath('foo/bar', 'foo');

		$this->assertTrue($isMatching);
	}

	public function testCapturingMatch()
	{
		$isMatching = $this->object->matchPath('foo', '(..)o');

		$this->assertTrue($isMatching);
	}

	public function testGetMatchedPathFragment()
	{
		$isMatching = $this->object->matchPath('foo', 'foo');
		$matchedFragment = $this->object->getMatchedPathFragment();

		$this->assertTrue($isMatching);
		$this->assertEquals('foo', $matchedFragment);
	}

	public function testFragmentMatchedAtBeginningDoesNotContainTrailingSlash()
	{
		$isMatching = $this->object->matchPath('foo/bar', 'foo');
		$matchedFragment = $this->object->getMatchedPathFragment();

		$this->assertTrue($isMatching);
		// Note the trailing slash because the matched path goes on after 'foo'
		$this->assertEquals('foo', $matchedFragment);
	}

	public function testGetBackrefReplacer()
	{
		$this->object->matchPath('foo123/bar', 'foo(\\d+)');
		$backrefReplacer = $this->object->getBackrefReplacer();

		$this->assertInstanceOf('XAF\\web\\routing\\BackrefReplacer', $backrefReplacer);
		$this->assertEquals('x123x', $backrefReplacer->process('x$1x'));
	}
}

