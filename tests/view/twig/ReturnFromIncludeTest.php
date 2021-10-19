<?php
namespace XAF\view\twig;

use TwigTestBase;

require_once __DIR__ . '/TwigTestBase.php';

/**
 * @covers \XAF\view\twig\DefaultExtension
 * @covers \XAF\view\twig\ReturnTokenParser
 * @covers \XAF\view\twig\ReturnNode
 */
class ReturnFromIncludeTest extends TwigTestBase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->environment->addExtension(new DefaultExtension());
	}

	protected function getEnvironmentOptions()
	{
		// The XAF custom template class is required to make returning of values from included templates work
		return ['base_template_class' => 'XAF\\view\\twig\\Template'];
	}

	public function testReturnEndsProcessing()
	{
		$this->setupTemplate('template.twig', 'foo{% return %}bar');

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('foo', $result);
	}

	public function testValueCanBeReturnedFromIncludedTemplate()
	{
		$this->setupTemplate('template.twig', "{{ include('include.twig') }}");
		$this->setupTemplate('include.twig', "{% return 'foo' %}");

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('foo', $result);
	}


	public function testParentContextIsPassedToIncludedTemplate()
	{
		$this->setupTemplate('template.twig', "{% set var = 'foo' %}{% set result = include('include.twig') %}");
		$this->setupTemplate('include.twig', '{{ var }}');

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('foo', $result);
	}

	public function testIncludeFailsWithoutTemplateName()
	{
		$this->setupTemplate('template.twig', '{% set result = include() %}');
		$this->setupTemplate('include.twig', '{{ var }}');

		$this->expectException(\Twig\Error\LoaderError::class);
		$this->renderTemplate('template.twig');
	}
}
