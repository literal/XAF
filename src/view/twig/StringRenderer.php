<?php
namespace XAF\view\twig;

use Twig_Environment;

class StringRenderer
{
	/**
	 * @var Twig_Environment
	 */
	private $twigEnv;

	/**
	 * @param Twig_Environment $twigEnv
	 */
	public function __construct( Twig_Environment $twigEnv )
	{
		$this->twigEnv = $twigEnv;
	}

	/**
	 * @param string $templateString
	 * @param array $context the data made available to the string
	 * @return string
	 */
	public function render( $templateString, array $context = [] )
	{
		$template = $this->twigEnv->loadTemplate($templateString);
		return $template->render($context);
	}

}
