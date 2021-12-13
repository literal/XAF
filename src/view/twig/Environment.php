<?php
namespace XAF\view\twig;

use Twig\Environment as TwigEnvironment;

class Environment extends TwigEnvironment
{
	/**
     * Gets the template class associated with the given string.
     *
     * @param string $name The name for which to calculate the template class name
     *
     * @return string The template class name
     */
	/*public function getTemplateClass($name)
    {
        return '__TwigTpl_'.strtr($this->loader->getCacheKey($name), ':/().\\', '______');
    }*/
}

