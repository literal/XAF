<?php
namespace XAF\web;

use XAF\http\Request;
use XAF\helper\UrlHelper;

use XAF\exception\SystemError;

class DefaultUrlResolver implements UrlResolver
{
	/** @var Request */
	protected $request;

	/** @var string */
	protected $baseUrl;

	/** @var string */
	protected $rootPath = '';

	/** @var string */
	protected $basePath = '';

	/** @var array */
	protected $autoParams = [];

	public function __construct( Request $request )
	{
		$this->request = $request;
		$this->setBaseUrl($request->getBaseUrl());
	}

	/**
	 * @param string $baseUrl
	 */
	public function setBaseUrl( $baseUrl )
	{
		$this->baseUrl = \rtrim($baseUrl, '/') . '/';
	}

	/**
	 * @param string $rootPath
	 */
	public function setRootPath( $rootPath )
	{
		$this->rootPath = $this->normalizePath($rootPath);
	}

	/**
	 * @param string $basePath
	 */
	public function setBasePath( $basePath )
	{
		$this->basePath = $this->normalizePath($basePath);
	}

	/**
	 * @param string $path
	 * @return string
	 */
	private function normalizePath( $path )
	{
		$path = \trim($path, '/');
		return $path !== '' ? '/' . $path : '';
	}

	// ************************************************************************
	// Implementation of UrlResolver
	// ************************************************************************

	/**
	 * @param string $name
	 * @param string $value
	 */
	public function setAutoQueryParam( $name, $value )
	{
		$this->autoParams[$name] = $value;
	}

	/**
	 * @return array
	 */
	public function getAutoQueryParams()
	{
		return $this->autoParams;
	}

	/**
	 * @return string
	 */
	public function getBaseUrl()
	{
		return $this->baseUrl;
	}

	/**
	 * @return string
	 */
	public function getRootPath()
	{
		return $this->rootPath;
	}

	/**
	 * @return string
	 */
	public function getBasePath()
	{
		return $this->basePath;
	}

	/**
	 * @return string
	 */
	public function getCurrentPagePath()
	{
		return $this->urlPathToPagePath($this->request->getRequestPath());
	}

	/**
	 * @return array
	 */
	public function getCurrentQueryParams()
	{
		return $this->request->getQueryParams();
	}

	/*
	 * @return string
	 */
	public function getCurrentPagePathWithQuery()
	{
		return $this->getCurrentPagePath() . UrlHelper::buildQueryString($this->request->getQueryParams());
	}

	/**
	 * @param string $pagePath
	 * @param array $params
	 * @return string
	 */
	public function buildUrlPath( $pagePath, array $params = [] )
	{
		return UrlHelper::mergeQuery($this->pagePathToUrlPath($pagePath), $params);
	}

	/**
	 * @param string $pagePath
	 * @param array $params
	 * @return string
	 */
	public function buildAbsUrl( $pagePath, array $params = [] )
	{
		return UrlHelper::buildAbsoluteUrl($this->baseUrl, $this->buildUrlPath($pagePath, $params));
	}

	/**
	 * @param string $pagePath
	 * @param array $params
	 * @return string
	 */
	public function buildHref( $pagePath, array $params = [] )
	{
		return UrlHelper::mergeQuery($this->pagePathToUrlPath($pagePath), \array_replace($this->autoParams, $params));
	}

	/**
	 * @param string $pagePath
	 * @param array $params
	 * @return string
	 */
	public function buildAbsHref( $pagePath, array $params = [] )
	{
		return UrlHelper::buildAbsoluteUrl($this->baseUrl, $this->buildHref($pagePath, $params));
	}

	/**
	 * @param string $urlPath
	 * @return string
	 */
	public function urlPathToPagePath( $urlPath )
	{
		if( $this->rootPath === '' )
		{
			return $urlPath;
		}
		if( \strpos($urlPath, $this->rootPath) !== 0 )
		{
			throw new SystemError(
				'request path does not start with root path',
				$urlPath,
				'root path: ' . $this->rootPath
			);
		}
		return \substr($urlPath, \strlen($this->rootPath));
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected function pagePathToUrlPath( $path )
	{
		switch( true )
		{
			case $path === '.' || $path === '':
				return $this->rootPath . ($this->basePath !== '' ? $this->basePath : '/');

			case $path[0] === '/':
				return $this->rootPath . $path;

			case \strpos($path, './') === 0:
				return $this->rootPath . $this->basePath . \substr($path, 1);
		}
		return $this->rootPath . $this->basePath . '/' . $path;
	}
}
