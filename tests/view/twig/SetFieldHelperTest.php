<?php
namespace XAF\view\twig;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\view\twig\SetFieldHelper
 */
class SetFieldHelperTest extends TestCase
{
	public function testObjectInFieldChainThrowsException()
	{
		$context = ['hash' => ['object' => new \stdClass]];

		$this->expectException(\Twig\Error\RuntimeError::class);
		SetFieldHelper::assertFieldCanBeSet($context, ['hash', 'object', 'field']);
	}

	public function testScalarValueInFieldChainThrowsException()
	{
		$context = ['hash' => ['string' => 'foo']];

		$this->expectException(\Twig\Error\RuntimeError::class);
		SetFieldHelper::assertFieldCanBeSet($context, ['hash', 'string', 'field']);
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testNonExistentElementIsOk()
	{
		$context = ['hash' => []];

		SetFieldHelper::assertFieldCanBeSet($context, ['hash', 'element', 'subelement']);
	}
}
