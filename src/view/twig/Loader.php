<?php
namespace XAF\view\twig;

use Twig_LoaderInterface;

/**
 * Custom Twig template loader
 *
 * Loads templates from the file system like \Twig_Loader_Filesystem but adds some features:
 *
 * - Language fallback strategy:
 *
 *   A language qualifier ist set for the loader. For any requested template without the template file
 *   name extension, localized versions are searched. When the language qualifier 'de.ch' is set and the template
 *   'foo' is requested, the loader will first look for 'foo.de.ch.twig', then 'foo.de.twig' and eventually 'foo.twig'.
 *
 * - Named template paths:
 *
 *   By default the loader searches for a template in all the given template paths from first to last.
 *   If, however, paths are specified as a hash, the hash keys (followed by a colon) can be used before template names
 *   to specify where to load a template from.
 *
 *   If e.g. the template paths are set as {'app': '/tpl/app', 'common': '/tpl/common'} and the template
 *   'common:foo.twig' is requested, the loader will not try to access '/tpl/app/foo.twig' but go for
 *   '/tpl/common/foo.twig' directly.
 *
 *   There are two pre-defined path aliases:
 *   + 'default': refers to the second entry in the path list or below (!)
 *   + 'master': refers to the last entry in the path list
 */
class Loader implements Twig_LoaderInterface
{
	/** @var array */
	private $templatePaths;

	/** @var string */
	private $templateFileExtension;

	/**
	 * file name extensions to scan for - from most to least specific
	 * (e.g. ['.de.de.twig', '.de.twig', '.twig'])
	 * @var array
	 */
	private $extensionCandidates;

	private $templatePathCache = [];

	/**
	 * @param array|string $templatePaths Where to search for the templates files, can be multiple alternatives
	 *     for fall-back. When specified as a hash, the hash keys can be used as path aliases in template names like
	 *     this: 'path-alias:path/to/template.twig'.
	 * @param string|null $templateQualifier Qualifier to search for before the final file name extension when a
	 *    requested template is specified without extensions.
	 *    The qualifier is normally the language tag in dot-separated lower-case notation.
	 *    E. g. 'en.us' would mean search for "<template>.en.us.twig", then "<template>.en.twig" and
	 *    finally "<template>.twig".
	 * @param string $templateFileExtension Extension to add to every template file name that is specified
	 *     without it, must include the leading dot.
	 */
	public function __construct( $templatePaths = [], $templateQualifier = null, $templateFileExtension = '.twig' )
	{
		$this->setTemplatePaths($templatePaths);
		$this->templateFileExtension = $templateFileExtension;
		$this->setTemplateQualifier($templateQualifier);
	}

	/**
	 * @param array|string $templatePaths Where to search for the templates files, can be multiple alternatives
	 *     for fall-back. When specified as a hash, the hash keys can be used as path aliases in template names like
	 *     this: 'path-alias:path/to/template.twig'.
	 */
	public function setTemplatePaths( $templatePaths )
	{
		$this->templatePathCache = [];
		$this->templatePaths = [];
		foreach( (array)$templatePaths as $alias => $templatePath )
		{
			$this->templatePaths[$alias] = \rtrim($templatePath, '\\/');
		}
	}

	/**
	 * @param string|null $templateQualifier Qualifier to search for before the final file name extension when a
	 *    requested template is specified without extensions.
	 *    The qualifier is normally the language tag in dot-separated lower-case notation.
	 *    E. g. 'en.us' would mean search for "<template>.en.us.twig", then "<template>.en.twig" and
	 *    finally "<template>.twig".
	 */
	public function setTemplateQualifier( $templateQualifier )
	{
		$this->templatePathCache = [];
		$this->extensionCandidates = [];
		if( $templateQualifier )
		{
			$qualifierParts = \explode('.', $templateQualifier);
			while( $qualifierParts )
			{
				$this->extensionCandidates[] = '.' . \implode('.', $qualifierParts) . $this->templateFileExtension;
				\array_pop($qualifierParts);
			}
		}
		$this->extensionCandidates[] = $this->templateFileExtension;
	}

	/**
	 * Gets the source code of a template, given its name.
	 *
	 * @param string $locator string The template to load
	 * @return string The template source code
	 */
	public function getSource( $locator )
	{
		return \file_get_contents($this->findTemplate($locator));
	}

	/**
	 * Gets the cache key to use for the cache for a given template name.
	 *
	 * @param string $locator string The template to load
	 * @return string The cache key
	 */
	public function getCacheKey( $locator )
	{
		return $this->findTemplate($locator);
	}

	/**
	 * Returns true if the template is still fresh.
	 *
	 * @param string $locator The template to check
	 * @param int $time The last modification time of the cached template
	 * @return bool
	 */
	public function isFresh( $locator, $time )
	{
		return \filemtime($this->findTemplate($locator)) <= $time;
	}

	/**
	 * @param string $locator
	 * @return string
	 */
	protected function findTemplate( $locator )
	{
		if( isset($this->templatePathCache[$locator]) )
		{
			return $this->templatePathCache[$locator];
		}

		$nameComponents = \explode(':', $locator, 2);
		if( \sizeof($nameComponents) > 1 )
		{
			$paths = $this->getTemplatePathsByAlias($nameComponents[0]);
			$templateName = $nameComponents[1];
		}
		else
		{
			$paths = $this->templatePaths;
			$templateName = $locator;
		}

		$extensions = $this->hasTemplateFileExtension($templateName) ? [''] : $this->extensionCandidates;

		$templateFile = $this->locateTemplateFile($templateName, $paths, $extensions);
		$this->templatePathCache[$locator] = $templateFile;
		return $templateFile;
	}

	/**
	 * @param string $alias
	 * @return array
	 */
	private function getTemplatePathsByAlias( $alias )
	{
		if( $alias == 'default' && \sizeof($this->templatePaths) > 1 )
		{
			return \array_slice($this->templatePaths, 1);
		}

		if( $alias == 'master' )
		{
			return \array_slice($this->templatePaths, -1);
		}

		if( isset($this->templatePaths[$alias]) )
		{
			return [$this->templatePaths[$alias]];
		}

		throw new TwigTemplateNotFoundError('unknown template path alias', $alias);
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	private function hasTemplateFileExtension( $name )
	{
		return \substr($name, -\strlen($this->templateFileExtension)) == $this->templateFileExtension;
	}

	private function locateTemplateFile( $name, array $paths, array $extensions )
	{
		$this->assertNoDirectoryTraversal($name);

		$filesTried = [];
		foreach( $paths as $path )
		{
			foreach( $extensions as $extension )
			{
				$candidate = $path . '/' . $name . $extension;
				$filesTried[] = $candidate;
				if( \is_file($candidate) )
				{
					return $candidate;
				}
			}
		}

		throw new TwigTemplateNotFoundError(
			'template not found',
			$name,
			'tried files: [' . \implode('], [', $filesTried) . ']'
		);
	}

	/**
	 * @param string $templateName
	 */
	private function assertNoDirectoryTraversal( $templateName )
	{
		if( \strpos($templateName, '..') !== false )
		{
			throw new TwigTemplateNotFoundError('template path must not contain \'..\'', $templateName);
		}
	}
}
