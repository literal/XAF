<?php
namespace XAF\view\twig;

use TwigTestBase;

require_once __DIR__ . '/TwigTestBase.php';

/**
 * @covers \XAF\view\twig\Loader
 */
class LoaderTest extends TwigTestBase
{
	/**
	 * @return Loader
	 */
	protected function createLoader()
	{
		return new Loader($this->templatePath, null, '.twig');
	}

	public function testTemplateExistensionIsAddedWhenRequestedTemplateHasNone()
	{
		$this->setupTemplate('template.twig', 'foo');

		$result = $this->renderTemplate('template'); // No '.twig' here!

		$this->assertEquals('foo', $result);
	}

	public function testTemplateExistensionIsNotAddedWhenRequestedTemplateHasIt()
	{
		$this->setupTemplate('template.twig', 'foo');

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('foo', $result);
	}

	public function testTemplateExistensionIsAlsoAddedForInternalTemplateReferences()
	{
		$this->setupTemplate('template.twig', "{% extends 'parent' %}"); // No '.twig' here!
		$this->setupTemplate('parent.twig', "{% include 'include' %}"); // No '.twig' here!
		$this->setupTemplate('include.twig', 'inc');

		$result = $this->renderTemplate('template'); // No '.twig' here!

		$this->assertEquals('inc', $result);
	}

	public function testLanguageQualifierIsUsedIfMatchingTemplateExists()
	{
		$this->loader->setTemplateQualifier('en.us');
		$this->setupTemplate('template.twig', 'common');
		$this->setupTemplate('template.en.twig', 'en');
		$this->setupTemplate('template.en.us.twig', 'en-us');

		$result = $this->renderTemplate('template'); // No '.twig' here!

		$this->assertEquals('en-us', $result);
	}

	public function testLanguageQualifierFallsBackToMoreGeneralLanguage()
	{
		$this->loader->setTemplateQualifier('en.gb');
		$this->setupTemplate('template.twig', 'common');
		$this->setupTemplate('template.en.twig', 'en');
		$this->setupTemplate('template.en.us.twig', 'en-us');

		$result = $this->renderTemplate('template'); // No '.twig' here!

		$this->assertEquals('en', $result);
	}

	public function testLanguageQualifierIsNotUsedWhenTemplateExtensionIsSpecified()
	{
		$this->loader->setTemplateQualifier('en');
		$this->setupTemplate('template.en.twig', 'en');
		$this->setupTemplate('template.twig', 'common');

		$result = $this->renderTemplate('template.twig'); // '.twig' here prevents use of language qualifier

		$this->assertEquals('common', $result);
	}

	public function testTemplatePathsAreSearchedInTheSpecifiedOrder()
	{
		$this->loader->setTemplatePaths([
			$this->templatePath . '/main',
			$this->templatePath . '/common'
		]);
		$this->setupTemplate('main/template.twig', 'main');
		$this->setupTemplate('common/template.twig', 'common');

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('main', $result);
	}

	public function testLoaderFallsBackToNextPathWhenTemplateIsNotFound()
	{
		$this->loader->setTemplatePaths([
			$this->templatePath . '/main',
			$this->templatePath . '/common'
		]);
		$this->setupTemplate('common/template.twig', 'common');

		$result = $this->renderTemplate('template.twig');

		$this->assertEquals('common', $result);
	}

	public function testPathAliasSelectsTemplatePathDirectly()
	{
		$this->loader->setTemplatePaths([
			'main' => $this->templatePath . '/main',
			'common' => $this->templatePath . '/common',
		]);
		$this->setupTemplate('main/template.twig', 'main');
		$this->setupTemplate('common/template.twig', 'common');

		$result = $this->renderTemplate('common:template.twig');

		$this->assertEquals('common', $result);
	}

	public function testPathAliasDisablesFallbackToParentTemplatePath()
	{
		$this->loader->setTemplatePaths([
			'main' => $this->templatePath . '/main',
			'common' => $this->templatePath . '/common',
		]);
		$this->setupTemplate('common/template.twig', 'common');

		// There is no template.twig in path 'main' and there shall be no fallback to 'common' because
		// 'main:' is explicitly specified.
		$this->expectException(\XAF\view\twig\TwigTemplateNotFoundError::class);
		$this->renderTemplate('main:template.twig');
	}

	public function testPredefinedPathAliasMasterRefersToLastTemplatePathEntry()
	{
		$this->loader->setTemplatePaths([
			'main' => $this->templatePath . '/main',
			'common' => $this->templatePath . '/common',
			'top' => $this->templatePath . '/top',
		]);
		$this->setupTemplate('main/template.twig', 'main');
		$this->setupTemplate('common/template.twig', 'common');
		$this->setupTemplate('top/template.twig', 'top');

		$result = $this->renderTemplate('master:template.twig');

		$this->assertEquals('top', $result);
	}

	public function testPredefinedPathAliasDefaultRefersToSecondTemplatePathEntry()
	{
		$this->loader->setTemplatePaths([
			'main' => $this->templatePath . '/main',
			'common' => $this->templatePath . '/common',
			'top' => $this->templatePath . '/top',
		]);
		$this->setupTemplate('main/template.twig', 'main');
		$this->setupTemplate('common/template.twig', 'common');
		$this->setupTemplate('top/template.twig', 'top');

		$result = $this->renderTemplate('default:template.twig');

		$this->assertEquals('common', $result);
	}

	public function testPredefinedPathAliasDefaultAllowsFallbackToParentPath()
	{
		$this->loader->setTemplatePaths([
			'main' => $this->templatePath . '/main',
			'common' => $this->templatePath . '/common',
			'top' => $this->templatePath . '/top',
		]);
		$this->setupTemplate('main/template.twig', 'main');
		$this->setupTemplate('top/template.twig', 'top');

		$result = $this->renderTemplate('default:template.twig');

		$this->assertEquals('top', $result);
	}

	public function testPredefinedPathAliasDefaultRequiresAtLeastTwoTemplatePaths()
	{
		$this->loader->setTemplatePaths([
			'main' => $this->templatePath . '/main',
		]);
		$this->setupTemplate('main/template.twig', 'main');

		$this->expectException(\XAF\view\twig\TwigTemplateNotFoundError::class);
		$this->expectExceptionMessage('unknown template path alias');
		$this->renderTemplate('default:template.twig');
	}

	public function testNonExistentTemplateFileThrowsException()
	{
		$this->expectException(\XAF\view\twig\TwigTemplateNotFoundError::class);
		$this->renderTemplate('template.twig');
	}

	public function testUnknownPathAliasThrowsException()
	{
		$this->setupTemplate('template.twig', 'foo');

		$this->expectException(\XAF\view\twig\TwigTemplateNotFoundError::class);
		$this->expectExceptionMessage('unknown template path alias');
		$this->renderTemplate('somepath:template.twig');
	}

	public function testTemplateNameMustNotContainDirectoryBackRef()
	{
		$this->setupTemplate('template.twig', 'foo');

		$this->expectException(\XAF\view\twig\TwigTemplateNotFoundError::class);
		$this->expectExceptionMessage('template path must not contain \'..\'');
		$this->renderTemplate('foo/../template.twig');
	}
}
