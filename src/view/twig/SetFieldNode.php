<?php
namespace XAF\view\twig;

use Twig_NodeInterface;
use Twig_Node;
use Twig_Compiler;
use Twig_Node_Expression_Constant;
use Twig_Node_Text;

class SetFieldNode extends Twig_Node
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
		if( $value instanceof Twig_Node_Text )
		{
			$this->setAttribute('capture', false);
			$this->setNode('value', new Twig_Node_Expression_Constant($value->getAttribute('data'), $value->getLine()));
		}
	}

	public function compile( Twig_Compiler $compiler )
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

	private function compileBlockCaptureAssignment( Twig_Compiler $compiler )
	{
		$compiler->write('ob_start();' . "\n");
		$compiler->subcompile($this->getNode('value'));
		$compiler->write('$_value = new Twig_Markup(ob_get_clean(), $this->env->getCharset());' . "\n");

		$this->compileFieldKeyChain($compiler);
		$this->compileValidTargetRuntimeCheck($compiler);
		$this->compileLeftSideOfAssignment($compiler);
		$compiler->raw('$_value;' . "\n");
	}

	private function compileExpressionAssignment( Twig_Compiler $compiler )
	{
		$this->compileFieldKeyChain($compiler);
		$this->compileValidTargetRuntimeCheck($compiler);
		$this->compileLeftSideOfAssignment($compiler);
		$valueNode = $this->getNode('value');
		if( $this->getAttribute('safe') )
		{
			$compiler->raw('new Twig_Markup(')->subcompile($valueNode)->raw(', $this->env->getCharset())');
		}
		else
		{
			$compiler->subcompile($valueNode);
		}
		$compiler->raw(';' . "\n");
	}

	private function compileFieldKeyChain( Twig_Compiler $compiler )
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

	private function compileValidTargetRuntimeCheck( Twig_Compiler $compiler )
	{
		$lineNumber = \intval($this->lineno) ?: -1;
		$compiler->write(
			'\\XAF\\view\\twig\\SetFieldHelper::assertFieldCanBeSet'
			. '($context, $_keys, ' . $lineNumber . ', $this->getTemplateName());'
			. "\n"
		);
	}

	private function compileLeftSideOfAssignment( Twig_Compiler $compiler )
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
