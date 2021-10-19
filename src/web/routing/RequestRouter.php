<?php
namespace XAF\web\routing;

use XAF\exception\SystemError;
use XAF\web\exception\PageNotFound;
use XAF\web\exception\BadRequest;

/**
 * Perform translation of HTTP request path according to a routing tree
 *
 * Somewhat similar to what Apache mod_rewrite does
 */
class RequestRouter
{
	/** @var PathPatternMatcher */
	protected $pathMatcher;

	/** @var ControlRouteBuilder */
	protected $routeBuilder;

	/** @var RoutingResult */
	protected $result;

	/** @var array */
	protected $routingTable = [];

	/** @var string */
	protected $requestMethod;

	/** @var string */
	protected $matchedPath;

	/** @var string */
	protected $remainingPath;

	public function __construct( PathPatternMatcher $pathPatternMatcher, ControlRouteBuilder $controlRouteBuilder )
	{
		$this->pathMatcher = $pathPatternMatcher;
		$this->routeBuilder = $controlRouteBuilder;
	}

	/*
	 * Set the routing table which has the following format:
	 *
	 *   {
	 *       // Optional, follow route only if expression is true, ignored for top-level entry
	 *     'if': <'varname'|'varname=value'>,
	 *
	 *       // Optional, follow route only if expression is false, ignored for top-level entry
	 *     'unless': <'varname'|'varname=value'>,
	 *
	 *       // Optional, add matched request path fragment to base path, ignored for top-level entry and any
	 *       // routes flagged 'continue'
	 *     'basepath': <bool>,
	 *
	 *		 // Optional, do NOT end routing process after this entry. There MUST be another matching route after
	 *       // this or the request path will count as unresolved. Further routing results will be gathered ON TOP of
	 *       // the existing ones. 'continue' is ignored for the top-level entry.
	 *     'continue': <bool>,
	 *
	 *       // Optional, named parameters to include in routing result
	 *       // - Can be 'GETVAL', 'POSTVAL', 'REQUESTVAL' (either POST or GET) or 'COOKIE' with optional
	 *       //   field name in parenthesis (e. g. 'GETVAL(id)') to capture values from the HTTP request,
	 *       //   without parenthesis the default for the field name is the var name
	 *       // - Should include a validation rule after a colon, e. g. '$1:int' or 'POSTVAL:string'
	 *       // Unlike filters, actions and catches, vars are not reset by an internal redirect but new vars
	 *       // from the extra routing pass are mrged with the existing ones.
	 *     'vars': {<var name>: <string with regex backrefs²>, ...},
	 *
	 *       // All Optional, filters and controller methods to invoke for a route. Further items encountered in
	 *       // sub-routes are accumulated unless items are named and and override previous items of the same name
	 *       // Filter format is: <filter object key>[: param=value, ...]
	 *       // Action format is: <controller object key>:<Method Name>[(param, ...)]
	 *     'infilters': <array|hash|string¹>,
	 *     'actions': <array|hash|string¹>,
	 *     'outfilters': <array|hash|string¹>,

	 *       // Optional, cumulated along followed route fragments: Exceptions to catch and issue an internal
	 *       // redirect for (normally for displaying a custom error page)
	 *     'catch': {
	 *       <fully qualified exception class name> => <internal redirect path>,
	 *       ...
	 *     },
	 *
	 *       // Optional, forget all previously collected filters/actions/definitions. Either a single token or an
	 *       // array of multiple tokens: 'vars', 'infilters', 'outfilters', 'actions' and 'catch'.
	 *     'reset': <array|string>,
	 *
	 *       // Optional sub-routes, patterns will be matched against the remainder of the path
	 *       // after the current match, further vars, filters and actions can be added to the ones already set
	 *     'routes': {
	 *		   // A partial preg pattern: no regex delimiters, implicit ^ at the beginning,
	 *         // implicit / or $ at the end, starts with a slash for a public URL path (fragment), convention
	 *         // for internal redirect paths is to begin with a '@'
	 *       <regex>: {
	 *         // Another routing table entry
	 *       },
	 *       ...
	 *     }
	 *   }
	 *
	 * As a shortcut, a route can also be just a string instead of an array (i.e. <regex> => <string>) -
	 * this string will then be used as the action².
	 *
	 * ¹ Can be either {'foo': 'Foo', 'bar': 'Bar'} (keys are arbitrary and only to allow overwriting items in
	 *   sub-routes) or ['Foo', 'Bar'] or for single items without a key just 'Foo'.
	 *
	 * ² Backrefs to groups captured by the patterns can be used: $1 .. $9 - attention: there is no
	 *   escape for '$<digit>' (i.e. '$' can't be used as in literals if followed by a number)!
	 *
	 * @param array $routingTable
	 */
	public function setRoutingTable( array $routingTable )
	{
		$this->routingTable = $routingTable;
	}

	/**
	 * Builds a slash-separated result route from the request path using the routing table,
	 * may also extract parameters on the way and build a common base path
	 *
	 * @param string $requestMethod
	 * @param string $requestPath
	 * @return RoutingResult
	 *
	 * @throws SystemError If the routing table is malformed
	 * @throws PageNotFound If the route could not be matched
	 * @throws BadRequest If validation failed for a request var
	 */
	public function resolveRoute( $requestMethod, $requestPath )
	{
		$this->requestMethod = $requestMethod;
		$this->remainingPath = $requestPath;
		$this->matchedPath = '';

		$this->result = new RoutingResult;
		$this->routeBuilder->setResultObject($this->result);

		// Process global settings that apply to all routes
		$this->applyRouteFragment($this->routingTable);

		if( !isset($this->routingTable['routes']) )
		{
			throw new SystemError('routing table has no routes');
		}

		if( !$this->matchRemainingPath($this->routingTable['routes']) )
		{
			throw new PageNotFound($requestPath, 'unresolved request route');
		}

		$this->result->resolved = true;
		return $this->result;
	}

	/**
	 * Used to fetch the (incomplete) routing result after an exception has occurred during routing
	 *
	 * @return RoutingResult
	 */
	public function getResult()
	{
		return $this->result;
	}

	/**
	 * @param array|string $fragment
	 * @param BackrefReplacer $backrefReplacer
	 */
	protected function applyRouteFragment( $fragment, BackrefReplacer $backrefReplacer = null )
	{
		// Apply unconditional entries
		$this->routeBuilder->applyRouteFragment($fragment, $backrefReplacer);

		// Apply request method specific entries
		if( \is_array($fragment) && isset($fragment['methods']) )
		{
			foreach( $fragment['methods'] as $method => $fragment )
			{
				if( $method == '*' || $method == $this->requestMethod )
				{
					$this->routeBuilder->applyRouteFragment($fragment, $backrefReplacer);
				}
			}
		}
	}

	/**
	 * Searches for a matching route part and applies it if found
	 *
	 * @param array $routingTable
	 * @return bool Whether a matching route was found
	 */
	protected function matchRemainingPath( array $routingTable )
	{
		foreach( $routingTable as $pattern => $route )
		{
			if( !$this->isRouteActive($route) )
			{
				continue;
			}

			if( $this->pathMatcher->matchPath($this->remainingPath, $pattern) )
			{
				if( \is_array($route) && isset($route['continue']) && $route['continue'] )
				{
					$this->followRouteAndContinue($route, $this->pathMatcher->getBackrefReplacer());
				}
				else
				{
					return $this->followRoute($route, $this->pathMatcher->getBackrefReplacer());
				}
			}
		}

		return false;
	}

	/**
	 * @param array|string $route
	 * @return bool
	 */
	protected function isRouteActive( $route )
	{
		if( \is_array($route) )
		{
			if( isset($route['if']) && !$this->isConditionTrue($route['if']) )
			{
				return false;
			}
			if( isset($route['unless']) && $this->isConditionTrue($route['unless']) )
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Computes a conndition expression testing a request var
	 *
	 * @param string $expression
	 * @return bool
	 */
	protected function isConditionTrue( $expression )
	{
		$parts = \explode('=', $expression, 2);
		$varName = $parts[0];
		$expectedValue = isset($parts[1]) ? $parts[1] : null;
		$actualValue = isset($this->result->vars[$varName]) ? $this->result->vars[$varName] : null;

		if( $expectedValue === null )
		{
			return $actualValue !== null && $actualValue !== '' && $actualValue !== false;
		}

		return $expectedValue == $actualValue;
	}

	/**
	 * Follow current route *as far as possible*, applying all matching entries to the routing result
	 * and preserve current remaining path to allow routing to continue
	 *
	 * @param array $route a routing table entry
	 * @param BackrefReplacer $backrefReplacer
	 */
	protected function followRouteAndContinue( $route, BackrefReplacer $backrefReplacer = null )
	{
		$preservedMatchedPath = $this->matchedPath;
		$preservedRemainingPath = $this->remainingPath;
		$preservedBasePath = $this->result->basePath;

		$this->followRoute($route, $backrefReplacer);

		$this->matchedPath = $preservedMatchedPath;
		$this->remainingPath = $preservedRemainingPath;
		$this->result->basePath = $preservedBasePath;
	}

	/**
	 * Try to follow current route down to the end (i. e. until all of the request path has been matched against
	 * routing table entries)
	 *
	 * @param array|string $route a routing table entry
	 * @param BackrefReplacer $backrefReplacer
	 * @return bool Whether there was a route that could be followed to the end
	 */
	protected function followRoute( $route, BackrefReplacer $backrefReplacer = null )
	{
		$matchedPathFragment = $this->pathMatcher->getMatchedPathFragment();

		$this->matchedPath .= $matchedPathFragment;
		$this->stripFragmentFromRemainingPath($matchedPathFragment);

		if( \is_array($route) && isset($route['basepath']) && $route['basepath'] )
		{
			$this->captureBasePath();
		}

		$this->applyRouteFragment($route, $backrefReplacer);

		return \is_array($route) && isset($route['routes'])
			? $this->matchRemainingPath($route['routes'])
			: $this->isRequestPathFullyMatched();
	}

	/**
	 * Removes a matched path fragment from the beginning of the remaining path to work with
	 *
	 * @param string $fragment
	 */
	protected function stripFragmentFromRemainingPath( $fragment )
	{
		if( $fragment === '' )
		{
			return;
		}

		$pathLength = \strlen($this->remainingPath);
		$fragmentLength = \strlen($fragment);
		$this->remainingPath = $fragmentLength < $pathLength
			? \substr($this->remainingPath, $fragmentLength)
			: '';
	}

	protected function captureBasePath()
	{
		$this->result->basePath = $this->matchedPath;
	}

	/**
	 * @return bool
	 */
	protected function isRequestPathFullyMatched()
	{
		return $this->remainingPath === '';
	}
}
