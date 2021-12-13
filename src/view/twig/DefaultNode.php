<?php
namespace XAF\view\twig;

use Twig\Node\Node;
use Twig_NodeInterface;
use Twig\Compiler;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\TextNode;

class DefaultNode extends Node
{
	/**
	 * @param string $variableName
	 * @param Twig_NodeInterface $value
	 * @param bool $capture
	 * @param int $lineNumber
	 * @param string $tagName
	 */
	public function __construct( $variableName, Twig_NodeInterface $value, $capture, $lineNumber, $tagName = null )
	{
		parent::__construct(
			['value' => $value],
			['variableName' => $variableName, 'capture' => $capture, 'safe' => false],
			$lineNumber,
			$tagName
		);

		if( $this->getAttribute('capture') )
		{
			$this->setAttribute('safe', true);
			$this->convertCapturedBlockValueToConstantIfPossible();
		}
	}

	private function convertCapturedBlockValueToConstantIfPossible()
	{
		$value = $this->getNode('value');
		if( $value instanceof TextNode )
		{
			$this->setAttribute('capture', false);
			$this->setNode('value', new ConstantExpression($value->getAttribute('data'), $value->getLine()));
		}
	}

	public function compile( Compiler $compiler )
	{
		$compiler->addDebugInfo($this);

		$variableName = $this->getAttribute('variableName');
		$compiler->write('if (')
			->raw('!array_key_exists(')->string($variableName)->raw(', $context)')
			->raw(') {' . "\n");
		$compiler->indent();

		if( $this->getAttribute('capture') )
		{
			$this->compileBlockCaptureAssignment($compiler);
		}
		else
		{
			$this->compileExpressionAssignment($compiler);
		}

		$compiler->outdent();
		$compiler->write('}' . "\n");
	}

	private function compileBlockCaptureAssignment( Compiler $compiler )
	{
		$compiler->write('ob_start();' . "\n");
		$compiler->subcompile($this->getNode('value'));
		$compiler->raw('$_value = new \\Twig\\Markup(ob_get_clean(), $this->env->getCharset());' . "\n");
		$this->compileLeftSideOfAssignment($compiler);
		$compiler->raw('$_value;' . "\n");
	}

	private function compileExpressionAssignment( Compiler $compiler )
	{
		$this->compileLeftSideOfAssignment($compiler);

		$valueNode = $this->getNode('value');
		if( $this->getAttribute('safe') )
		{
			$compiler->raw('new \\Twig\\Markup(')->subcompile($valueNode)->raw(', $this->env->getCharset())');
		}
		else
		{
			$compiler->subcompile($valueNode);
		}
		$compiler->raw(';' . "\n");
	}

	private function compileLeftSideOfAssignment( Compiler $compiler )
	{
		$variableName = $this->getAttribute('variableName');
		$compiler->write('$context[')->string($variableName)->raw(']')->raw(' = ');
	}
}
