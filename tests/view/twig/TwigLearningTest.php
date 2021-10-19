<?php

require_once __DIR__ . '/TwigTestBase.php';

class TwigLearningTest extends TwigTestBase
{
	/**
	 * This test demonstates we can *not* extend a base template and override variables defined in the base template's
	 * global part (i. e. the part outside any named block)
	 */
	public function testDerivedTemplateGlobalBlockIsExecutedBeforeParent()
	{
		$this->setupTemplate(
			'parent.twig',
			"{% set commonVar = 'common' %}" . // Same var name is assigned in the derived template
			'{{ commonVar }} {{ derivedOnlyVar }}'
		);
		$this->setupTemplate(
			'derived.twig',
			"{% extends 'parent.twig' %}" .
			"{% set commonVar = 'override' %}" . // This has no effect, the assignment is overwritten by the base template!
			"{% set derivedOnlyVar = 'derived' %}"
		);

		$result = $this->renderTemplate('derived.twig');

		$this->assertEquals('common derived', $result);
	}
}
