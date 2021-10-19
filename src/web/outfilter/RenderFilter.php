<?php
namespace XAF\web\outfilter;

use XAF\view\TemplateRenderer;
use XAF\web\Response;

class RenderFilter extends OutputFilter
{
	/** @var string */
	private $template;

	/** @var TemplateRenderer */
	private $renderer;

	public function __construct( $template, TemplateRenderer $renderer )
	{
		$this->template = $template;
		$this->renderer = $renderer;
	}

	public function execute( Response $response )
	{
		$templateContext = $response->data;

		$response->result = $this->renderer->render($this->template, $templateContext);
	}
}
