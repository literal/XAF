<?php

use XAF\exception\SystemError;
use Twig\TwigFunction;

require_once __DIR__ . '/TwigTestBase.php';

/**
 * @covers \XAF\view\twig\Template
 */
class TemplateTest extends TwigTestBase
{
	/**
	 * @return array
	 */
	protected function getEnvironmentOptions()
	{
		return [
			'base_template_class' => '\\XAF\\view\\twig\\Template',
			'autoescape' => false
		];
	}

	/**
	 * Twig does by default wrap any exceptions thrown during rendering into Twig exceptions so the template
	 * and line number can be displayed. This interferes with the global handling of these exceptions, though.
	 * E. g. exceptions meant to result in specific HTTP responses (like a 404 response for a PageNotFound
	 * exception).
	 */
	public function testExceptionsDuringRenderingAreNotWrappedInTwigExceptions()
	{
		$this->environment->addFunction(new TwigFunction('fail', 'TemplateTest::throwSystemError'));
		$this->setupTemplate('template.twig', '{{ fail() }}');

		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('I have failed');
		$this->renderTemplate('template.twig');
	}

	/**
	 * And: Twig even implements this twice! Once for the template as a whole and once for rendering of blocks.
	 */
	public function testExceptionsDuringBlockRenderingAreNotWrappedInTwigExceptions()
	{
		$this->environment->addFunction(new TwigFunction('fail', 'TemplateTest::throwSystemError'));
		$this->setupTemplate('template.twig', '{% block foo %}{{ fail() }}{% endblock %}');

		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('I have failed');
		$this->renderTemplate('template.twig');
	}

	/**
	 * To be called from within Twig template
	 */
	static public function throwSystemError()
	{
		throw new SystemError('I have failed');
	}
}
