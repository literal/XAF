<?php
namespace XAF\view\twig;

use Twig_NodeInterface;
use Twig\Node\Node;
use Twig\Compiler;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\TextNode;

class SetFieldNode extends Node
{
	/**
	 * @param array $fieldKeyChain
	 * @param Twig_NodeInterface $value
	 * @param bool $capture
	 * @param int $lineNumber
	 * @param string $tagName
	 */
	public function __construct(
		array $fieldKeyChain,
		Twig_NodeInterface $value,
		$capture,
		$lineNumber,
		$tagName = null
	)
	{
		parent::__construct(
			['value' => $value],
			['fieldKeyChain' => $fieldKeyChain, 'capture' => $capture, 'safe' => false],
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

		if( $this->getAttribute('capture') )
		{
			$this->compileBlockCaptureAssignment($compiler);
		}
		else
		{
			$this->compileExpressionAssignment($compiler);
		}
	}

	private function compileBlockCaptureAssignment( Compiler $compiler )
	{
		$compiler->write('ob_start();' . "\n");
		$compiler->subcompile($this->getNode('value'));
		$compiler->write('$_value = new \\Twig\\Markup(ob_get_clean(), $this->env->getCharset());' . "\n");

		$this->compileFieldKeyChain($compiler);
		$this->compileValidTargetRuntimeCheck($compiler);
		$this->compileLeftSideOfAssignment($compiler);
		$compiler->raw('$_value;' . "\n");
	}

	private function compileExpressionAssignment( Compiler $compiler )
	{
		$this->compileFieldKeyChain($compiler);
		$this->compileValidTargetRuntimeCheck($compiler);
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

	private function compileFieldKeyChain( Compiler $compiler )
	{
		$fieldKeyNodes = $this->getAttribute('fieldKeyChain');

		$compiler->write('$_keys = [');

		foreach( $fieldKeyNodes as $fieldKeyNode )
		{
			if( $fieldKeyNode )
			{
				$compiler->subcompile($fieldKeyNode);
			}
			else
			{
				$compiler->raw('null');
			}
			$compiler->raw(',');
		}
		$compiler->raw('];' . "\n");
	}

	private function compileValidTargetRuntimeCheck( Compiler $compiler )
	{
		$lineNumber = \intval($this->lineno) ?: -1;
		$compiler->write(
			'\\XAF\\view\\twig\\SetFieldHelper::assertFieldCanBeSet'
			. '($context, $_keys, ' . $lineNumber . ', $this->getTemplateName());'
			. "\n"
		);
	}

	private function compileLeftSideOfAssignment( Compiler $compiler )
	{
		$compiler->write('$context');

		$keyIndex = 0;
		foreach( $this->getAttribute('fieldKeyChain') as $fieldKeyNode )
		{
			if( $fieldKeyNode )
			{
				$compiler->raw('[$_keys[' . $keyIndex . ']]');
			}
			else
			{
				$compiler->raw('[]');
			}
			$keyIndex++;
		}

		$compiler->raw(' = ');
	}
}
