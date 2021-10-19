<?php
namespace XAF\view\twig;

use TwigTestBase;

require_once __DIR__ . '/TwigTestBase.php';

/**
 * @covers \XAF\view\twig\DefaultTokenParser
 * @covers \XAF\view\twig\DefaultNode
 */
class DefaultNodeTest extends TwigTestBase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->environment->addTokenParser(new DefaultTokenParser());
	}

	public function testDefaultSetsNonExistentValue()
	{
		$this->setupTemplate(
			'template.twig',
			"{% default foo = 'bar' %}\n" .
			'{{ foo }}'
		);

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('bar', $result);
	}

	public function testDefaultDoesNotOverwriteExistingValue()
	{
		$this->setupTemplate(
			'template.twig',
			"{% default foo = 'bar' %}\n" .
			'{{ foo }}'
		);

		$result = $this->renderTemplate('template.twig', ['foo' => 'foo']);

		$this->assertEquals('foo', $result);
	}

	public function testDefaultCapturesTextInBlockSyntax()
	{
		$this->setupTemplate(
			'template.twig',
			"{% default foo %}bar{% enddefault %}\n" .
			'{{ foo }}'
		);

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('bar', $result);
	}

	public function testDefaultCapturesComplexContentInBlockSyntax()
	{
		$this->setupTemplate(
			'template.twig',
			"{% default foo %}-{{ bar }}-{% enddefault %}\n" .
			'{{ foo }}'
		);

		$result = $this->renderTemplate('template.twig', ['bar' => 'bar']);

		$this->assertEquals('-bar-', $result);
	}

	public function testDefaultCanBeNested()
	{
		$this->setupTemplate(
			'template.twig',
			'{% default outerVar %}' .
				"{% default innerVar %}{{ 'value' }}{% enddefault %}" .
				'inner: {{ innerVar }}' .
			"{% enddefault %}\n" .
			'{{ outerVar }}'
		);

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('inner: value', $result);
	}
}
