<?php
namespace XAF\view\twig;

use XAF\view\TemplateRenderer;
use Twig\Environment;
use Twig\Template;

use XAF\exception\SystemError;
use XAF\view\TemplateNotFoundError;

class Renderer implements TemplateRenderer
{
	/** @var Environment */
	private $twigEnv;

	public function __construct( Environment $twigEnv )
	{
		$this->twigEnv = $twigEnv;
	}

	public function setTemplatePaths( $templatePaths )
	{
		$templateLoader = $this->twigEnv->getLoader();
		if( !\method_exists($templateLoader, 'setTemplatePaths') )
		{
			throw new SystemError(
				'Template loader does not support setting of template paths',
				\get_class($templateLoader)
			);
		}
		$templateLoader->setTemplatePaths($templatePaths);
	}

	/**
	 * @param string $templateName
	 * @param array $context the data made available to the template
	 * @return string
	 */
	public function render( $templateName, array $context = [] )
	{
		$template = $this->loadTemplate($templateName);

		try
		{
			return $template->render($context);
		}
		catch( TwigTemplateNotFoundError $e )
		{
			$this->rethrowNotFoundError($e);
		}
	}

	/**
	 * @param string $templateName
	 * @param string $blockName
	 * @param array $context the data made available to the template
	 * @return string|null
	 */
	public function renderNamedBlock( $templateName, $blockName, array $context = [] )
	{
		$template = $this->loadTemplate($templateName);
		try
		{
			$result = $template->renderBlock($blockName, $context);
			return $result !== '' ? $result : null;
		}
		catch( TwigTemplateNotFoundError $e )
		{
			$this->rethrowNotFoundError($e);
		}
	}

	/**
	 * @param strring $templateName
	 * @return Template
	 */
	private function loadTemplate( $templateName )
	{
		try
		{
			return $this->twigEnv->loadTemplate($templateName);
		}
		catch( TwigTemplateNotFoundError $e )
		{
			$this->rethrowNotFoundError($e);
		}
	}

	private function rethrowNotFoundError( TwigTemplateNotFoundError $e )
	{
		// Rethrow the Twig error as XAF error so it can be properly handled by the application
		throw new TemplateNotFoundError(
			$e->getOriginalMessage(),
			$e->getOriginalValue(),
			$e->getErrorDetails()
		);
	}
}
