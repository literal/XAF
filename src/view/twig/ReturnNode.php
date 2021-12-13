<?php
namespace XAF\view\twig;

use Twig\Node\Node;
use Twig\Compiler;

class ReturnNode extends Node
{
	public function __construct( $valueNode, $lineNumber, $tag = null )
	{
		parent::__construct(['value' => $valueNode], [], $lineNumber, $tag);
	}

	/**
	 * @param Compiler $compiler
	 */
	public function compile( Compiler $compiler )
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
