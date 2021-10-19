<?php
namespace XAF\web\routing;

use XAF\exception\SystemError;

/**
 * Helper for the routing process
 * Applies a matching entry from the routing tree to the routing result
 */
class ControlRouteBuilder
{
	/** @var RequestVarResolver */
	protected $requestVarResolver;

	/** @var RoutingResult all processing results are written to this public value object */
	protected $result;

	/** @var BackrefReplacer */
	protected $backrefReplacer;

	public function __construct( RequestVarResolver $requestVarResolver )
	{
		$this->requestVarResolver = $requestVarResolver;
	}

	/**
	 * @param RoutingResult $routingResult all processing results are written to this object
	 */
	public function setResultObject( RoutingResult $routingResult )
	{
		$this->result = $routingResult;
	}

	/**
	 * @param array|string $route a routing table entry (a mere string is treated as a single action)
	 * @param BackrefReplacer $backrefReplacer fills in any dynamic parts of the routing result (i.e. values captured from the request URL)
	 */
	public function applyRouteFragment( $route, BackrefReplacer $backrefReplacer = null )
	{
		$this->backrefReplacer = $backrefReplacer;

		if( \is_string($route) )
		{
			$route = ['actions' => $route];
		}

		foreach( $route as $key => $value )
		{
			switch( $key )
			{
				case 'reset':
					$this->handleReset($value);
					break;

				case 'infilters':
					$this->setFiltersOrActions($value, $this->result->inputFilters);
					break;

				case 'actions':
					$this->setFiltersOrActions($value, $this->result->actions);
					break;

				case 'outfilters':
					$this->setFiltersOrActions($value, $this->result->outputFilters);
					break;

				case 'vars':
					$this->setRequestVars($value);
					break;

				case 'catch':
					$this->setExceptionRedirects($value);
					break;
			}
		}
	}

	protected function handleReset( $whatToReset )
	{
		$whatToReset = (array)$whatToReset;

		if( \in_array('infilters', $whatToReset) )
		{
			$this->result->inputFilters = [];
		}
		if( \in_array('actions', $whatToReset) )
		{
			$this->result->actions = [];
		}
		if( \in_array('outfilters', $whatToReset) )
		{
			$this->result->outputFilters = [];
		}
		if( \in_array('vars', $whatToReset) )
		{
			$this->result->vars = [];
		}
		if( \in_array('catch', $whatToReset) )
		{
			$this->result->exceptionRedirects = [];
		}
	}

	protected function setFiltersOrActions( $items, array &$target )
	{
		foreach( (array)$items as $key => $value )
		{
			if( \is_int($key) )
			{
				$target[] = $this->processValue($value);
			}
			else
			{
				$target[$key] = $this->processValue($value);
			}
		}
	}

	protected function setRequestVars( $varDefs )
	{
		if( !\is_array($varDefs) )
		{
			throw new SystemError('invalid routing table entry - \'vars\' is not an array', $varDefs);
		}

		foreach( $varDefs as $key => $varDef )
		{
			$this->result->vars[$key] = $this->requestVarResolver->resolveVar($key, $this->processValue($varDef));
		}
	}

	protected function setExceptionRedirects( $redirectDefs )
	{
		if( !\is_array($redirectDefs) )
		{
			throw new SystemError('invalid routing table entry - \'catch\' is not an array', $redirectDefs);
		}

		$this->result->exceptionRedirects = \array_merge($this->result->exceptionRedirects, $redirectDefs);
	}

	protected function processValue( $value )
	{
		if( !\is_string($value) )
		{
			return $value;
		}
		if( $value === '' )
		{
			return null;
		}
		return isset($this->backrefReplacer) ? $this->backrefReplacer->process($value) : $value;
	}
}
