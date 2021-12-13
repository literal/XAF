<?php
namespace XAF\view\twig;

use Twig\Node\Expression\Binary\AbstractBinary;
use Twig\Compiler;

class BeginsWithOperator extends AbstractBinary
{
	/**
	 * Compile the node to PHP
	 *
	 * @param Compiler $compiler
	 */
	public function compile( Compiler $compiler )
	{
		$compiler
			->raw('(strpos(')
			->subcompile($this->getNode('left'))
			->raw(', ')
			->subcompile($this->getNode('right'))
			->raw(') === 0)');
	}

	public function operator( Compiler $compiler )
	{
		return $compiler->raw('beginswith');
	}
}
