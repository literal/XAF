<?php
namespace XAF\view\twig;

use Twig_Template;

/**
 * Specify this class in the 'base_template_class'-option when creating the Twig_Environment instance.
 *
 * Modifications to the original Template class:
 * - disable catching of non-Twig extensions and wrapping them in a Twig extension
 * - return value from 'display' to make 'return' node work (in combination with overridden 'include' node)
 */
abstract class Template extends Twig_Template
{
	/**
	 * @param array $context
	 * @param array $blocks
	 * @return array|null
	 */
	public function display( array $context, array $blocks = [] )
	{
		return $this->doDisplay($this->env->mergeGlobals($context), \array_merge($this->blocks, $blocks));
	}

	/**
	 * Unfortunately a copy of the original method minus the error intrerception code, because error handling
	 * was not factored out into a separate method for displayBlock() like it was for display().
	 *
	 * @param string  $name      The block name to display
	 * @param array   $context   The context
	 * @param array   $blocks    The current set of blocks
	 * @param bool    $useBlocks Whether to use the current set of blocks
	 */
	public function displayBlock( $name, array $context, array $blocks = [], $useBlocks = true )
	{
		$name = (string) $name;

		if( $useBlocks && isset($blocks[$name]) )
		{
			$template = $blocks[$name][0];
			$block = $blocks[$name][1];
		}
		else if( isset($this->blocks[$name]) )
		{
			$template = $this->blocks[$name][0];
			$block = $this->blocks[$name][1];
		}
		else
		{
			$template = null;
			$block = null;
		}

		if( null !== $template )
		{
			$template->$block($context, $blocks);
		}
		else if( false !== ($parent = $this->getParent($context)) )
		{
			$parent->displayBlock($name, $context, \array_merge($this->blocks, $blocks), false);
		}
	}
}
