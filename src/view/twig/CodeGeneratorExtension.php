<?php
namespace XAF\view\twig;

use Twig_Extension;
use Twig_Filter_Function;

class CodeGeneratorExtension extends Twig_Extension
{
	/**
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return array An array of functions
	 */
	public function getFilters()
	{
		return [
			'underscoreId' => new Twig_Filter_Function('XAF\\helper\\CodeGeneratorHelper::toUnderscoreIdentifier'),
			'titleCaseId' => new Twig_Filter_Function('XAF\\helper\\CodeGeneratorHelper::toTitleCaseIdentifier'),
			'camelCaseId' => new Twig_Filter_Function('XAF\\helper\\CodeGeneratorHelper::toCamelCaseIdentifier'),
			'camelCaseToWords' => new Twig_Filter_Function('XAF\\helper\\CodeGeneratorHelper::camelCaseToWords'),
			'regexEscape' => new Twig_Filter_Function('XAF\\helper\\CodeGeneratorHelper::regexEscape'),
			'phpStringLiteral' => new Twig_Filter_Function('XAF\\helper\\CodeGeneratorHelper::toPhpStringLiteral'),
		];
	}

	/**
	 * @return string extension key in hash
	 */
	public function getName()
	{
		return 'XAFCodeGenerator';
	}
}
