<?php
namespace XAF\view\twig;

use TwigTestBase;

require_once __DIR__ . '/TwigTestBase.php';

/**
 * Test XAF default Twig extension adding various features
 *
 * @covers \XAF\view\twig\DefaultExtension
 */
class DefaultExtensionTest extends TwigTestBase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->environment->addExtension(new DefaultExtension());
	}

	public function testRoundFilter()
	{
		$this->setupTemplate('template.twig', '{{ value | round }}');

		$result = $this->renderTemplate('template.twig', ['value' => 23.46]);

		$this->assertEquals('23', $result);
	}

	public function testRoundFilterWithPrecision()
	{
		$this->setupTemplate('template.twig', '{{ value | round(1) }}');

		$result = $this->renderTemplate('template.twig', ['value' => 23.46]);

		$this->assertEquals('23.5', $result);
	}

	public function testFloorFilter()
	{
		$this->setupTemplate('template.twig', '{{ value | floor }}');

		$result = $this->renderTemplate('template.twig', ['value' => 23.99]);

		$this->assertEquals('23', $result);
	}

	public function testCeilFilter()
	{
		$this->setupTemplate('template.twig', '{{ value | ceil }}');

		$result = $this->renderTemplate('template.twig', ['value' => 23.01]);

		$this->assertEquals('24', $result);
	}

	public function testLimitFilter()
	{
		$this->setupTemplate('template.twig', '{{ value | limit(2, 5) }}');

		$result = $this->renderTemplate('template.twig', ['value' => 16]);

		$this->assertEquals('5', $result);
	}

	public function testJsLiteralFilter()
	{
		$this->setupTemplate('template.twig', '{{ value | jsLiteral }}');

		$result = $this->renderTemplate('template.twig', ['value' => ['foo' => 'FOO', 'bar' => 'BAR']]);

		$this->assertEquals('{"foo":"FOO","bar":"BAR"}', $result);
	}

	public function testDumpFilter()
	{
		$this->setupTemplate('template.twig', '{{ value | dump }}');

		$result = $this->renderTemplate('template.twig', ['value' => [1, 2]]);

		$this->assertEquals("0: 1\n1: 2", $result);
	}

	public function testBase64Filter()
	{
		$this->setupTemplate('template.twig', '{{ value | base64 }}');

		$result = $this->renderTemplate('template.twig', ['value' => 'foo-bar']);

		$this->assertEquals('Zm9vLWJhcg==', $result);
	}

	public function testDeepMergeFilter()
	{
		$this->setupTemplate(
			'template.twig',
			"{% set var = {foo: {bar: 'boom'}} | deepMerge({foo: {baz: 'quux'}}) %}"
			. '{{ var.foo.bar }} {{ var.foo.baz }}'
		);

		$result = $this->renderTemplate('template.twig', []);

		$this->assertEquals('boom quux', $result);
	}

	/**
	 * Access to PHP constants is disabled because they may contain sensisive configuration information.
	 * Instead the given constant name will be returned as a string
	 */
	public function testConstantFunctionIsDisabled()
	{
		$this->setupTemplate('template.twig', "{{ constant('PATH_SEPARATOR') }}");

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('PATH_SEPARATOR', $result);
	}

	public function testCurrentDateIsSet()
	{
		$this->setupTemplate('template.twig', '{{ currentDate() }}');

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals(\date('Y-m-d'), $result);
	}
}
