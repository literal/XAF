<?php
namespace XAF\view;

interface TemplateRenderer
{
	/**
	 * @param array|string $templatePaths Where to search for the templates files, can be multiple alternatives
	 *     for fall-back.
	 */
	public function setTemplatePaths( $templatePaths );

	/**
	 * render a template and return the result
	 *
	 * @param string $templateName
	 * @param array $context the data made available to the template
	 * @return string
	 */
	public function render( $templateName, array $context = [] );

	/**
	 * render only a single named content block of a template
	 *
	 * @param string $templateName
	 * @param string $blockName
	 * @param array $context the data made available to the template
	 * @return string|null null the the block does not exists
	 */
	public function renderNamedBlock( $templateName, $blockName, array $context = [] );
}
