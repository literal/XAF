<?php
namespace XAF\view\twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TokenParser\TokenParserInterface;
use Twig\ExpressionParser;

use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Adds various useful items to Twig
 *
 * Tags:
 *
 *   return:   {% return <expression> %}
 *			   If the current template was loaded from another template via the 'include' function (function, not
 *             tag! Seee below!), this returns the given value for the including template.
 *             'return' ends the processing of the current template.
 *             ### NOTE: For 'return' to work, the XAF template base class must be used. ###
 *
 *   default:  {% default foo = 'bar' %} eqivalent to {% set foo = foo | default('bar') %}
 *             Short form for setting a value if it is not yet defined. Useful in a base template that is extended
 *             by some other template. The "global block" (i.e. the area outside any named block) sets a variable,
 *             with "{% set ... %}", the extending template cannot override it, because its global block is evaluated
 *             first. Using "{% default ... %}" instead of "{% set ... %}" in the base template fixes this.
 *
 *   setfield: {% setfield hash['key'] = value %}
 *             {% setfield hash.key = value %}
 *             {% setfield hash.key %}value{% endsetfield %}
 *             {% setfield hash['key'].key['key'].key = value %}
 *             {% setfield array[] = value %} {# appends element to scalar array #}
 *             ### ATTENTION: Will hrow a runtime exception when trying to set field on a scalar value or object! ###
 *
 * Functions:
 *
 *   include:  Load another template and yield the value returned via the 'return' tag (see above).
 *
 *   currentDate: Return current date in YYYY-MM-DD format
 *
 *   constant: Overwrites built-in 'constant' function to disable access to PHP constant from within Twig
 *             templates. Constants may contain sensitive configuration settings that untrusted templates shall
 *             not have access to. Instead of the constant's value the constant name is returned.
 *
 * Filters:
 *
 *   round:     {{ 23.26 | round(1) }} -> 23.3
 *              round number to the specified precision or to interger if no precision specified
 *
 *   floor:     {{ 23.81 | floor }} -> 23
 *              round number down to nearest lower integer
 *
 *   ceil:      {{ 23.26 | ceil }} -> 24
 *              round number up to nearest higher integer
 *
 *   limit:     {{ 133 | limit(10, 100) }} -> 100
 *              limit numerical value by a min and/or max value (both limits are optional)
 *
 *   dump:	    {{ var | dump( [maxNestingDepth] ) }}
 *			    produce dump of a template variable
 *
 *   jsLiteral: {{ {'foo"': 'bar', 'boom': 31} | jsLiteral }} -> {"foo\"":"bar","boom":31}
 *              {{ 'foo' | jsLiteral }} -> "foo"
 *			    create Javascript literal from PHP data
 *
 *   base64:    {{ 'foobar' | base64 }}
 *              Base-64 encode string value
 *
 *   deepMerge: {% set data = {foo: {bar: 'boom'}} | deepMerge({foo: {baz: 'quux'}}) %}
 *				-> data == {foo: {bar: 'boom', baz: 'quux'}}
 *
 * Operators:
 *
 *   beginswith: {% if var beginswith 'foobar' %}
 *			   binary operator checking whether left side string argument is contained in the
 *			   beginning of the right side string argument, boolean result
 *
 */
class DefaultExtension extends AbstractExtension
{
	/**
	 * Returns the token parser instance to add to the existing list.
	 *
	 * @return TokenParserInterface[] An array of TokenParserInterface instances
	 */
	public function getTokenParsers()
	{
		return [
			new ReturnTokenParser(),
			new DefaultTokenParser(),
			new SetFieldTokenParser()
		];
	}

	/**
	 * Returns a list of filters to add to the existing list.
	 *
	 * @return array An array of filters
	 */
	public function getFilters()
	{
		return [
			new TwigFilter('round', 'round'),
			new TwigFilter('floor', 'floor'),
			new TwigFilter('ceil', 'ceil'),
			new TwigFilter('limit', 'XAF\\helper\\MathHelper::limit'),
			new TwigFilter('dump', 'XAF\\view\\twig\\DefaultExtension::dumpFilter'),
			new TwigFilter(
				'jsLiteral',
				'XAF\\helper\\JavascriptHelper::buildLiteral',
				['is_safe' => ['js']]
			),
			new TwigFilter('base64', 'base64_encode'),
			new TwigFilter('deepMerge', 'array_replace_recursive'),
		];
	}

	/**
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return array An array of functions
	 */
	public function getFunctions()
	{
		return [
			new TwigFunction(
				'include',
				'XAF\\view\\twig\\DefaultExtension::includeFunction',
				['needs_environment' => true, 'needs_context' => true]
			),
			// Override Twig core function 'constant' because it is a security threat
			// (allows access to configuration values stored in global constants)
			new TwigFunction('constant', 'strval'),
			new TwigFunction(
				'currentDate',
				function() { return \date('Y-m-d'); }
			)
		];
	}

	/**
	 * Returns a list of operators to add to the existing list.
	 *
	 * @return array An array of operators
	 */
	public function getOperators()
	{
		return [
			// unary operators
			[],
			// binary operators
			[
				'beginswith' => [
					'precedence' => 20, // like other comparison operators in the core extension, e.g. '=='
					'class' => '\\XAF\\view\\twig\\BeginsWithOperator',
					'associativity' => ExpressionParser::OPERATOR_LEFT
				]
			]
		];
	}

	/**
	 * Returns the name of the extension.
	 *
	 * @return string The extension name
	 */
	public function getName()
	{
		return 'XAFdefault';
	}

	/**
	 * @param string $value
	 * @param int $maxNestingLevel
	 * @return string
	 */
	static public function dumpFilter( $value, $maxNestingLevel = 8 )
	{
		static $dumper = null;
		if( !$dumper )
		{
			$dumper = new StructureDumper();
		}
		$dumper->setMaxNestingLevel($maxNestingLevel);
		return $dumper->dump($value);
	}

	/**
	 * @param Environment $env
	 * @param array $context
	 * @param string $templateName
	 */
	static public function includeFunction( Environment $env, array $context, $templateName = '' )
	{
		$template = $env->loadTemplate($templateName);
		return $template->display($context);
	}
}
