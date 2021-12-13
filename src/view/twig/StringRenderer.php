<?php
namespace XAF\view\twig;

use Twig\Environment;

class StringRenderer
{
	/**
	 * @var Environment
	 */
	private $twigEnv;

	/**
	 * @param Environment $twigEnv
	 */
	public function __construct( Environment $twigEnv )
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
