<?php
namespace XAF\view\twig;

use TwigTestBase;

require_once __DIR__ . '/TwigTestBase.php';

/**
 * @covers \XAF\view\twig\BeginsWithOperator
 */
class BeginswithOperatorTest extends TwigTestBase
{
	protected function setUp(): void
	{
		parent::setUp();
		// Twig_Environment is missing an 'addOperator' method, so we need the extension to provide the operator
		$this->environment->addExtension(new DefaultExtension());
	}

	public function testBeginswithFindsSubstringAtBeginning()
	{
		$this->setupTemplate(
			'template.twig',
			"{% if 'foobarboom' beginswith 'foo' %}true{%endif %}"
		);

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('true', $result);
	}

	public function testBeginswithFindsFullSubject()
	{
		$this->setupTemplate(
			'template.twig',
			"{% if 'foo' beginswith 'foo' %}true{%endif %}"
		);

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('true', $result);
	}

	public function testBeginswithDoesNotFindSubstringWithinSubject()
	{
		$this->setupTemplate(
			'template.twig',
			"{% if 'foobarboom' beginswith 'bar' %}true{%endif %}"
		);

		$result = $this->renderTemplate('template.twig');

		$this->assertEmpty($result);
	}
}
