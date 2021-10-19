<?php
namespace XAF\web\routing;

/**
 * Result data structure returned by RequestRouter
 */
class RoutingResult
{
	/**
	 * Whether a route for the given request path was found or and no exception was intercepted during routing
	 * (normally this would be XAF\web\exception\BadRequest instances thrown for invalid request var contents).
	 *
	 * Even if the route has not been resolved, there may be a meaningful collection of filters and request
	 * vars collected when following the routing table entries up to the point where the rest of the request
	 * path could not be matched anymore.
	 *
	 * @var bool
	 */
	public $resolved = false;

	/**
	 * The common base used for relative URL construction - determined during the request routing
	 *
	 * @var string
	 */
	public $basePath = '';

	/**
	 * Scalar array of aliases and optional arguments for all input filters to run before the actions
	 *
	 * Array elements are strings:
	 * - either '<FilterAlias>' (an alias known to the DI container)
	 * - or '<FilterAlias>:<param>=<value>,<param>=<value>, ...'
	 *
	 * @var array
	 */
	public $inputFilters = [];

	/**
	 * Any number of action definitions (including zero!) as strings in a scalar array
	 *
	 * Format: '<ControllerAlias>:<MethodName>' (the alias is used to get the controller from the DI container)
	 *
	 * @var array
	 */
	public $actions = [];

	/**
	 * @var array same format as $inputFilters
	 */
	public $outputFilters = [];

	/**
	 * Hash of arbitrary vars extracted from the request URL during routing or set by a filter or an action
	 * Vars are can be used by filters and are the source for values passed to action methods
	 *
	 * @var array
	 */
	public $vars = [];

	/**
	 * Mappings from exception classes to internal redirect paths
	 *
	 * @var array
	 */
	public $exceptionRedirects = [];
}
