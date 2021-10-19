<?php
namespace XAF\web;

use Exception;

use XAF\web\routing\RequestRouter;
use XAF\event\EventDispatcher;
use XAF\type\ParamHolder;

use XAF\exception\SystemError;
use XAF\web\exception\HttpRedirect;
use XAF\web\exception\InternalRedirect;
use XAF\exception\UserlandError;

/**
 * ATTENTION! This class contains the two only hard-coded conventions in the web app framework:
 *
 * - For localized responses, the request var 'language' must be populated by the routing process or an input filter.
 *   The language cannot be injected into the FrontController, as is is dynamically established only after the
 *   FrontController starts its job.
 *
 * - If an exception is intercepted and an internal redirect executed for it, the FrontController stores the
 *   exception object in the request var '@error' for logging and debugging purposes.
 *
 * @event shutdown()
 */
class FrontController
{
	/** @var RequestRouter */
	protected $router;

	/** @var Executor */
	protected $executor;

	/** @var Redirector */
	protected $redirector;

	/** @var EventDispatcher */
	protected $eventDispatcher;

	/** @var ParamHolder */
	protected $requestVars;

	/** @var DefaultUrlResolver */
	protected $urlResolver;

	/** @var string */
	protected $requestMethod;

	/** @var int */
	protected $routingPass;

	/** @var int */
	protected $maxRoutingPasses = 4;

	/** @var array */
	protected $exceptionRedirectMap = [];

	public function __construct( RequestRouter $router, Executor $executor, Redirector $redirector,
		EventDispatcher $eventDispatcher, ParamHolder $requestVars, DefaultUrlResolver $urlResolver )
	{
		$this->router = $router;
		$this->executor = $executor;
		$this->redirector = $redirector;
		$this->eventDispatcher = $eventDispatcher;
		$this->requestVars = $requestVars;
		$this->urlResolver = $urlResolver;
	}

	/**
	 * @param int $count
	 */
	public function setMaxInternalRedirects( $count )
	{
		$this->maxRoutingPasses = 1 + $count;
	}

	/**
	 * @param string $requestMethod HTTP request method, e. g. 'GET' or 'POST'
	 * @param string $requestPath URL-path from the HTTP request
	 */
	public function handleHttpRequest( $requestMethod, $requestPath )
	{
		$this->routingPass = 0;
		$this->requestMethod = $requestMethod;
		$pagePath = $this->urlResolver->urlPathToPagePath($requestPath);
		$this->executeRequest($pagePath);
		$this->shutDown();
	}

	/**
	 * @param string $pagePath
	 * @param array $presetResponseData
	 */
	protected function executeRequest( $pagePath, $presetResponseData = [] )
	{
		$this->routingPass++;
		$this->assertMaxRedirectsNotExceeded();

		try
		{
			$this->handleRequest($pagePath);
			$this->executeActionsAndSendResponse($presetResponseData);
		}
		catch( Exception $e )
		{
			$this->handleException($e);
		}
	}

	protected function assertMaxRedirectsNotExceeded()
	{
		if( $this->routingPass > $this->maxRoutingPasses )
		{
			throw new SystemError('too many internal redirects', $this->routingPass - 1);
		}
	}

	/**
	 * Request vars and input filters are collected/called even if routing fails. This is to:
	 * - populate the 'language' request var to be able to display the error page in the appropriate language
	 * - execute auth filters that may throw an exception when access is denied - else we would leak wheather a
	 *   path exists or not inside a privileged part of the application (it could be tested from the outside
	 *   whether a certain path leads to "not found" or "access denied")
	 *
	 * @param string $pagePath
	 */
	protected function handleRequest( $pagePath )
	{
		try
		{
			$this->router->resolveRoute($this->requestMethod, $pagePath);
		}
		catch( Exception $e )
		{
			$this->applyRoutingResult();
			throw $e;
		}
		$this->applyRoutingResult();
	}

	protected function applyRoutingResult()
	{
		$routingResult = $this->router->getResult();

		// Only set base path for original HTTP request
		if( $this->routingPass < 2 )
		{
			$this->urlResolver->setBasePath($routingResult->basePath);
		}

		$this->requestVars->merge($routingResult->vars);
		$this->exceptionRedirectMap = $routingResult->exceptionRedirects;

		if( $this->routingPass < 2 )
		{
			$this->executor->callInputFilters($routingResult->inputFilters);
		}
	}

	protected function executeActionsAndSendResponse( array $presetResponseData = [] )
	{
		$routingResult = $this->router->getResult();
		$response = new Response;
		$response->data = $presetResponseData;
		$this->executor->executeActions($routingResult->actions, $response);
		$this->executor->callOutputFilters($routingResult->outputFilters, $response);
	}

	/**
	 * @param Exception $e
	 */
	protected function handleException( Exception $e )
	{
		if( $e instanceof HttpRedirect )
		{
			$this->redirector->redirect($e->getPath(), $e->getQueryParams(), $e->getFragment());
			return;
		}

		if( $e instanceof InternalRedirect )
		{
			$this->executeRequest($e->getPath());
			return;
		}

		$redirectPath = $this->getExceptionRedirectPath($e);
		if( $redirectPath !== null )
		{
			$this->requestVars->set('@error', $e);
			$this->executeRequest($redirectPath, $this->getExceptionViewContext($e));
			return;
		}

		throw $e;
	}

	/**
	 * @param Exception $e
	 * @return string|null
	 */
	protected function getExceptionRedirectPath( Exception $e )
	{
		foreach( $this->exceptionRedirectMap as $className => $redirectPath )
		{
			if( \is_a($e, $className) && $redirectPath !== null )
			{
				return $redirectPath;
			}
		}

		return null;
	}

	/**
	 * @param Exception $e
	 * @return array
	 */
	protected function getExceptionViewContext( Exception $e )
	{
		return $e instanceof UserlandError ? $e->getViewContext() : [];
	}

	protected function shutDown()
	{
		// finishing the HTTP response before potentially time-consuming shutdown event (e. g. causing
		// Doctrine flushing entities to DB) makes the app feel more responsive
		$this->closeHttpResponse();
		$this->eventDispatcher->triggerEvent('shutdown');
	}

	protected function closeHttpResponse()
	{
		if( \function_exists('fastcgi_finish_request') )
		{
			\fastcgi_finish_request();
		}
	}
}
