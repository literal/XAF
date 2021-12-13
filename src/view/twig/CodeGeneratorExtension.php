<?php
namespace XAF\view\twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CodeGeneratorExtension extends AbstractExtension
{
	/**
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return array An array of functions
	 */
	public function getFilters()
	{
		return [
			new TwigFilter('underscoreId', 'XAF\\helper\\CodeGeneratorHelper::toUnderscoreIdentifier'),
			new TwigFilter('titleCaseId', 'XAF\\helper\\CodeGeneratorHelper::toTitleCaseIdentifier'),
			new TwigFilter('camelCaseId', 'XAF\\helper\\CodeGeneratorHelper::toCamelCaseIdentifier'),
			new TwigFilter('camelCaseToWords', 'XAF\\helper\\CodeGeneratorHelper::camelCaseToWords'),
			new TwigFilter('regexEscape', 'XAF\\helper\\CodeGeneratorHelper::regexEscape'),
			new TwigFilter('phpStringLiteral', 'XAF\\helper\\CodeGeneratorHelper::toPhpStringLiteral'),
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
