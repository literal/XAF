<?php
namespace XAF\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\helper\MessageRenderer
 */
class MessageRendererTest extends TestCase
{
	public function testParamsAreReplaced()
	{
		$result = MessageRenderer::render('A %foo% and a %bar%.', ['foo' => 'dog', 'bar' => 'cat']);

		$this->assertEquals('A dog and a cat.', $result);
	}

	public function testQuestionMarksAreInsertedForUnknownParams()
	{
		$result = MessageRenderer::render('No %foo%, but a %bar%.', ['bar' => 'quux']);

		$this->assertEquals('No ?, but a quux.', $result);
	}

	public function testDoublePercentSignRepresentsLiteralPercentSign()
	{
		$result = MessageRenderer::render('25 %% more', []);

		$this->assertEquals('25 % more', $result);
	}

	public function testCallablePatternIsCalledWithParams()
	{
		$result = MessageRenderer::render(
			function( array $params ) {
				return 'Function result with foo = ' . $params['foo'];
			},
			['foo' => 'quux']
		);

		$this->assertEquals('Function result with foo = quux', $result);
	}
}
