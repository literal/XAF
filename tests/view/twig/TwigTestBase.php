<?php

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader as Loader;

use org\bovigo\vfs\vfsStream;

abstract class TwigTestBase extends TestCase
{
	/** @var string */
	protected $templatePath;

	/** @var Environment */
	protected $environment;

	/** @var Loader */
	protected $loader;

	static protected $testNumber = 0;

	protected function setUp(): void
	{
		$this->templatePath = $this->createTemplatePath();
		$this->loader = $this->createLoader();
		$this->environment = new Environment($this->loader, $this->getEnvironmentOptions());
	}

	protected function createTemplatePath()
	{
		// The template dir needs a different name for each test.
		// Even though the virtual file system is cleared before each test and nothing is cached by Twig itself,
		// PHP remembers the existence of classes created by Twig (the 'compiled' templates).
		// If the template dir changes, though, the class name of the compiled templates will, too.
		self::$testNumber++;
		$templateDir = 'templates' . self::$testNumber;

		vfsStream::setup($templateDir);
		return vfsStream::url($templateDir);
	}

	/**
	 * @return TwigLoader
	 */
	protected function createLoader()
	{
		return new Loader($this->templatePath);
	}

	/**
	 * @return array
	 */
	protected function getEnvironmentOptions()
	{
		return ['autoescape' => false];
	}

	protected function setupTemplate( $file, $contents )
	{
		$dir = $this->templatePath . '/' . \ltrim(\dirname($file), '\\/');
		if( !\file_exists($dir) )
		{
			\mkdir($dir, 0777, true);
		}
		$file = $this->templatePath . '/' . \ltrim($file, '\\/');
		\file_put_contents($file, $contents);
	}

	protected function renderTemplate( $template, array $context = [] )
	{
		return $this->environment->render($template, $context);
	}

	protected function compileTemplateToPhp( $template )
	{
		$twigSource = $this->loader->getSource($template);
		return $this->environment->compileSource($twigSource, $template);
	}
}
