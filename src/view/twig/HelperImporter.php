<?php
namespace XAF\view\twig;

use Twig_Extension;
use Twig_Extension_GlobalsInterface;
use Twig_Filter_Method;
use Twig_Function_Method;
use XAF\di\Locator;

/**
 * Make view helpers available inside Twig templates
 */
class HelperImporter extends Twig_Extension implements Twig_Extension_GlobalsInterface
{
	/** @var Locator */
	protected $helperLocator;

	/** @var array */
	protected $filterMap = [];

	/** @var array */
	protected $functionMap = [];

	/** @var array */
	protected $globalMap = [];

	/**
	 * @param Locator $helperLocator
	 */
	public function __construct( Locator $helperLocator )
	{
		$this->helperLocator = $helperLocator;
	}

	public function getName()
	{
		return 'XAFimport';
	}

	/**
	 * Set the helper methods to be imported as Twig filters which can be used like this inside
	 * templates: "{{ value | filter }}"
	 *
	 * For each filter, at least an alias and a method name must be specified. The alias is the one requested
	 * from the helper locator to get the helper object. Optionally a hash of of Twig filter options can
	 * be specified (eventually passed to the constructor of Twig_Filter).
	 *
	 * @param array $filterMap {<filter name>: [<helper>, <method>[, <options>]], ...}
	 */
	public function setFilterMap( array $filterMap )
	{
		$this->filterMap = $filterMap;
	}

	/**
	 * Set the helper methods to be imported as Twig functions which can be used like this inside
	 * templates: "{{ function(value) }}"
	 *
	 * For each function, at least an alias and a method name must be specified. The alias is the one requested
	 * from the helper locator to get the helper object. Optionally a hash of of Twig function options can
	 * be specified (eventually passed to the constructor of Twig_Function).
	 *
	 * @param array $functionMap {<function name>: [<helper>, <method>[, <options>]], ...}
	 */
	public function setFunctionMap( array $functionMap )
	{
		$this->functionMap = $functionMap;
	}

	/**
	 * Set the items to be added as Twig global variables which can be used like this inside
	 * templates: "{{ var }}"
	 *
	 * The specified values are *not* the variable contents directly. They are:
	 * - either a helper alias for adding the helper object to the global template scope
	 * - or an arrays consisting of a helper alias and a method name to be called on the helper for getting the value
	 *
	 * @param array $globalMap {<variable name>: [<helper>[, <method>]], ...}
	 */
	public function setGlobalMap( array $globalMap )
	{
		$this->globalMap = $globalMap;
	}

	public function getFilters()
	{
		$filters = [];
		foreach( $this->filterMap as $filterName => $def )
		{
			$options = $def[2] ?? [];
			$filters[$filterName] =
				new Twig_Filter_Method($this, 'getHelper(\'' . $def[0] . '\')->' . $def[1], $options);
		}
		return $filters;
	}

	public function getFunctions()
	{
		$functions = [];
		foreach( $this->functionMap as $functionName => $def )
		{
			$options = $def[2] ?? [];
			$functions[$functionName] =
				new Twig_Function_Method($this, 'getHelper(\'' . $def[0] . '\')->' . $def[1], $options);
		}
		return $functions;
	}

	public function getGlobals()
	{
		$globals = [];
		foreach( $this->globalMap as $globalName => $def )
		{
			$helper = $this->getHelper($def[0]);
			$methodName = $def[1] ?? null;
			$globals[$globalName] = $methodName ? $helper->$methodName() : $helper;
		}
		return $globals;
	}

	public function getHelper( $key )
	{
		return $this->helperLocator[$key];
	}
}
