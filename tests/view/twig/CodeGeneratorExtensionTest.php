<?php
namespace XAF\view\twig;

use TwigTestBase;

require_once __DIR__ . '/TwigTestBase.php';

/**
 * Somke test (filters present and callable) only, functionality is tested in the helper test
 *
 * @covers \XAF\view\twig\CodeGeneratorExtension

 */
class CodeGeneratorExtensionTest extends TwigTestBase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->environment->addExtension(new CodeGeneratorExtension());
	}

	public function testUnderscoreIdFilter()
	{
		$this->setupTemplate('template.twig', "{{ '-foo  Bar *' | underscoreId }}");

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('foo_bar', $result);
	}

	public function testTitleCaseIdFilter()
	{
		$this->setupTemplate('template.twig', "{{ '-foo  Bar *' | titleCaseId }}");

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('FooBar', $result);
	}

	public function testCamelCaseIdFilter()
	{
		$this->setupTemplate('template.twig', "{{ '-foo  Bar *' | camelCaseId }}");

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('fooBar', $result);
	}

	public function testCamelCaseToWordsFilter()
	{
		$this->setupTemplate('template.twig', "{{ 'FooBar' | camelCaseToWords }}");

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('foo bar', $result);
	}

	public function testRegexEscapeFilter()
	{
		$this->setupTemplate('template.twig', "{{ 'foo(.+)bar' | regexEscape }}");

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('foo\\(\\.\\+\\)bar', $result);
	}


	public function testPhpStringLiteralFilter()
	{
		$this->setupTemplate('template.twig', "{{ 'foo \\' bar \\\\' | phpStringLiteral }}");

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals("'foo \\' bar \\\\'", $result);
	}
}

