<?php
namespace XAF\web\exception;

use Exception;

/**
 * Not an error but thrown to trigger a HTTP redirect
 */
class HttpRedirect extends Exception
{
	/**
	 * @var string|null
	 */
	protected $path;

	/**
	 * @var array
	 */
	protected $queryParams;

	/**
	 * @var string
	 */
	protected $fragment;

	/**
	 * @param string|null URL to redirect to, can be full URL, absolute path or relative path (relative to current UrlResolver base path)
	 * @param array $queryParams
	 * @param string|null $fragment URL hash ('#...')
	 */
	public function __construct( $path, $queryParams = [], $fragment = null )
	{
		$this->path = $path;
		$this->queryParams = $queryParams;
		$this->fragment = $fragment;
		parent::__construct(
			$path === null ? 'Redirection to current page' : 'Redirection to ' . $path
		);
	}

	/**
	 * @return string|null
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @return array
	 */
	public function getQueryParams()
	{
		return $this->queryParams;
	}

	/**
	 * @return string|null
	 */
	public function getFragment()
	{
		return $this->fragment;
	}
}
