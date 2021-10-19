<?php
namespace XAF\web;

use XAF\di\DiContainer;
use XAF\type\ParamHolder;

use XAF\web\infilter\InputFilter;
use XAF\web\outfilter\OutputFilter;

use XAF\exception\SystemError;

use XAF\helper\LanguageTagHelper;

/**
 * Calls filters and controller actions on behalf of the FrontController.
 *
 * Takes the filter and action definitions defined in the routing table and processed
 * by XAF\web\routing\ControlRouteBuilder.
 *
 * Prevents unexpected termination by calling ignore_user_abort befor any execution.
 * This way incomplete transactions or failure to release locks, write back data etc. is avoided.
 * Output filters will be skipped, though, if the connection is aborted by the client.
 *
 * Contollers that send their output directly to the client (e.g. because they create
 * progressive output that shall be sent to the client while the controller is running)
 * must check the connection status themselves!
 */
class Executor
{
	/** @var DiContainer */
	protected $diContainer;

	/** @var ParamHolder */
	protected $requestVars;

	/** @var string */
	protected $objectKeySuffix;

	public function __construct( DiContainer $diContainer, ParamHolder $requestVars )
	{
		$this->diContainer = $diContainer;
		$this->requestVars = $requestVars;
	}

	/**
	 * @param array $filterDefs
	 */
	public function callInputFilters( array $filterDefs )
	{
		\ignore_user_abort(true);
		foreach( $filterDefs as $filterDef )
		{
			if( $filterDef !== null )
			{
				$filter = $this->createFilter($filterDef); /* @var $filter InputFilter */
				$filter->execute();
			}
		}
	}

	/**
	 * @param array $actionDefs
	 * @param Response $response
	 */
	public function executeActions( array $actionDefs, Response $response )
	{
		\ignore_user_abort(true);
		foreach( $actionDefs as $actionDef )
		{
			if( $actionDef !== null )
			{
				$commandResult = $this->executeAction($actionDef);
				if( \is_array($commandResult) )
				{
					$response->data = \array_merge($response->data, $commandResult);
				}
			}
		}
	}

	/**
	 * @param array $filterDefs
	 * @param Response $response
	 */
	public function callOutputFilters( array $filterDefs, Response $response )
	{
		\ignore_user_abort(true);
		\ob_start();
		foreach( $filterDefs as $filterDef )
		{
			if( \connection_aborted() )
			{
				break;
			}
			if( $filterDef !== null )
			{
				$filter = $this->createFilter($filterDef);
				$filter->execute($response);
			}
		}

		if( \connection_aborted() )
		{
			\ob_end_clean();
		}
		else
		{
			\ob_end_flush();
			\flush();
		}
	}

	/**
	 * @param string $filterDef
	 * @return InputFilter|OutputFilter
	 */
	protected function createFilter( $filterDef )
	{
		$defParts = \explode(':', $filterDef, 2);

		$objectKey = $defParts[0];
		$objectKey = $this->localizeObjectKey($objectKey);
		$filter = $this->diContainer->create($objectKey);

		if( \sizeof($defParts) > 1 )
		{
			$filterParams = $this->extractFilterParams($defParts[1]);
			foreach( $filterParams as $key => $value )
			{
				$filter->setParam($key, $value);
			}
		}

		return $filter;
	}

	/**
	 * @param string $paramString
	 * @return array
	 */
	protected function extractFilterParams( $paramString )
	{
		$result = [];
		$paramDefs = \explode(',', $paramString);
		foreach( $paramDefs as $paramDef )
		{
			$paramParts = \explode('=', $paramDef, 2);
			if( \sizeof($paramParts) != 2 )
			{
				throw new SystemError('no \'=\' found in filter param definition', $paramDef);
			}
			$result[\trim($paramParts[0])] = \trim($paramParts[1]);
		}
		return $result;
	}

	/**
	 * @param string $actionDef
	 * @return array|null The respose from the action method, empty or hash
	 */
	protected function executeAction( $actionDef )
	{
		if( !\preg_match('/^([a-zA-Z0-9_]+):([a-zA-Z0-9_]+)(?:\\((.*)\\))?$/', $actionDef, $matches) )
		{
			throw new SystemError('invalid action', $actionDef);
		}

		$objectKey = $matches[1];
		$controller = $this->diContainer->get($this->localizeObjectKey($objectKey));
		$methodName = $matches[2];
		$arguments = isset($matches[3]) ? $this->resolveActionArguments($matches[3]) : [];

		if( !\method_exists($controller, $methodName) )
		{
			throw new SystemError('controller action method not found', \get_class($controller) . '::' . $methodName);
		}
		return \call_user_func_array([$controller, $methodName], $arguments);
	}

	/**
	 * @param string $argumentsString
	 * @return array
	 */
	protected function resolveActionArguments( $argumentsString )
	{
		$argumentsString = \trim($argumentsString);
		if( $argumentsString === '' )
		{
			return [];
		}

		$result = [];
		$argNames = \explode(',', $argumentsString);
		foreach( $argNames as $argName )
		{
			$result[] = $this->requestVars->get(\trim($argName));
		}
		return $result;
	}

	/**
	 * Append language specific object qualifier to object key
	 *
	 * Based on current contents of the 'language' request var
	 *
	 * @param string $objectKey
	 * @return string
	 */
	protected function localizeObjectKey( $objectKey )
	{
		$languageTag = $this->requestVars->get('language');
		$objectQualifier = LanguageTagHelper::toObjectQualifier($languageTag);
		return $objectKey . $objectQualifier;
	}
}
