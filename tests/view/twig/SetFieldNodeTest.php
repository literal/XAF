<?php
namespace XAF\view\twig;

use TwigTestBase;

require_once __DIR__ . '/TwigTestBase.php';

/**
 * @covers \XAF\view\twig\SetFieldTokenParser
 * @covers \XAF\view\twig\SetFieldNode
 * @covers \XAF\view\twig\SetFieldHelper
 */
class SetFieldNodeTest extends TwigTestBase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->environment->addTokenParser(new SetFieldTokenParser());
	}

	public function testFieldCanBeSetViaLiteralFieldKeyAfterDot()
	{
		$this->setupTemplate(
			'template.twig',
			"{% setfield hash.field = 'value' %}\n" .
			'{{ hash.field }}'
		);

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('value', $result);
	}

	public function testFieldCanBeSetViaKeyExpressionInSquareBrackets()
	{
		$this->setupTemplate(
			'template.twig',
			"{% setfield hash['fie' ~ 'ld'] = 'value' %}\n" .
			'{{ hash.field }}'
		);

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('value', $result);
	}

	public function testNestedSquareBracketsAreOkInFieldKeyExpression()
	{
		$this->setupTemplate(
			'template.twig',
			"{% set keys = ['field'] %}\n" .
			"{% setfield hash[keys[0]] = 'value' %}\n" .
			'{{ hash.field }}'
		);

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('value', $result);
	}

	public function testFieldCanBeAddedToExistingHash()
	{
		$this->setupTemplate(
			'template.twig',
			"{% setfield hash.newField = 'newValue' %}\n" .
			'{{ hash.oldField }} {{ hash.newField }}'
		);

		$result = $this->renderTemplate('template.twig', ['hash' => ['oldField' => 'oldValue']]);

		$this->assertEquals('oldValue newValue', $result);
	}

	public function testFieldCanBeAppendedToScalarArrayWithEmptySquareBrackets()
	{
		$this->setupTemplate(
			'template.twig',
			"{% setfield array[] = 'secondValue' %}\n" .
			'{{ array[0] }} {{ array[1] }}'
		);

		$result = $this->renderTemplate('template.twig', ['array' => ['firstValue']]);

		$this->assertEquals('firstValue secondValue', $result);
	}

	public function testFieldCanBeAddedToNestedHash()
	{
		$this->setupTemplate(
			'template.twig',
			"{% setfield hash.field.newSubField = 'newValue' %}\n" .
			'{{ hash.field.oldSubField }} {{ hash.field.newSubField }}'
		);

		$result = $this->renderTemplate(
			'template.twig',
			['hash' => ['field' => ['oldSubField' => 'oldValue']]]
		);

		$this->assertEquals('oldValue newValue', $result);
	}

	public function testDotAndBracketNotationCanBeMixed()
	{
		$this->setupTemplate(
			'template.twig',
			"{% setfield hash['field'].subField = 'value' %}\n" .
			'{{ hash.field.subField }}'
		);

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('value', $result);
	}

	public function testConstantValueCanBeCapturedWithBlockSyntax()
	{
		$this->setupTemplate(
			'template.twig',
			"{% setfield hash.field %}value{% endsetfield %}\n" .
			'{{ hash.field }}'
		);

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('value', $result);
	}

	public function testDynamicValueCanBeCapturedWithBlockSyntax()
	{
		$this->setupTemplate(
			'template.twig',
			"{% setfield hash.field %}{{ value }}{% endsetfield %}\n" .
			'{{ hash.field }}'
		);

		$result = $this->renderTemplate('template.twig', ['value' => 'value']);

		$this->assertEquals('value', $result);
	}

	public function testSetFieldCanBeNested()
	{
		$this->setupTemplate(
			'template.twig',
			'{% setfield outerHash.outerField %}' .
				"{% setfield innerHash.innerField %}{{ 'value' }}{% endsetfield %}" .
				'inner: {{ innerHash.innerField }}' .
			"{% endsetfield %}\n" .
			'{{ outerHash.outerField }}'
		);

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('inner: value', $result);
	}

	public function testSetFieldWorksLikeSetWhenNoKeyIsGiven()
	{
		$this->setupTemplate(
			'template.twig',
			"{% setfield var = 'value' %}\n" .
			'{{ var }}'
		);

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('value', $result);
	}

	public function testAssigningToFieldOfObjectThrowsException()
	{
		$this->setupTemplate(
			'template.twig',
			"{% setfield object.field = 'value' %}\n"
		);

		$this->expectException(\Twig\Error\RuntimeError::class);
		$this->renderTemplate('template.twig', ['object' => new \stdClass]);
	}

	public function testFieldContainingObjectCanBeOverwritten()
	{
		$this->setupTemplate(
			'template.twig',
			"{% setfield hash.field = 'value' %}\n" .
			'{{ hash.field }}'
		);

		$result = $this->renderTemplate('template.twig', ['hash' => ['field' => new \stdClass]]);

		$this->assertEquals('value', $result);
	}

	public function testFieldContainingScalarValueCanBeOverwritten()
	{
		$this->setupTemplate(
			'template.twig',
			"{% setfield hash.field = 'value' %}\n" .
			'{{ hash.field }}'
		);

		$result = $this->renderTemplate('template.twig', ['hash' => ['field' => 29]]);

		$this->assertEquals('value', $result);
	}
}
