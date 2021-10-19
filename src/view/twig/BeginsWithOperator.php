<?php
namespace XAF\view\twig;

use Twig_Node_Expression_Binary;
use Twig_Compiler;

class BeginsWithOperator extends Twig_Node_Expression_Binary
{
	/**
	 * Compile the node to PHP
	 *
	 * @param Twig_Compiler $compiler
	 */
	public function compile( Twig_Compiler $compiler )
	{
		$compiler
			->raw('(strpos(')
			->subcompile($this->getNode('left'))
			->raw(', ')
			->subcompile($this->getNode('right'))
			->raw(') === 0)');
	}

	public function operator( Twig_Compiler $compiler )
	{
		return $compiler->raw('beginswith');
	}
}
