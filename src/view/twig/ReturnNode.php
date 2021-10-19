<?php
namespace XAF\view\twig;

use Twig_Node;
use Twig_Compiler;

class ReturnNode extends Twig_Node
{
	public function __construct( $valueNode, $lineNumber, $tag = null )
	{
		parent::__construct(['value' => $valueNode], [], $lineNumber, $tag);
	}

	/**
	 * @param Twig_Compiler $compiler
	 */
	public function compile( Twig_Compiler $compiler )
	{
		$compiler->addDebugInfo($this);

		$compiler->write('return');
		$valueNode = $this->getNode('value');
		if( $valueNode )
		{
			$compiler->raw(' ');
			$compiler->subcompile($valueNode);
		}
		$compiler->raw(";\n");
	}
}
