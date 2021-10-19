<?php
namespace XAF\web\exception;

/**
 * Thrown to trigger a HTTP redirect to the currently requested page
 *
 * Normally used with different query params, e.g. with a different page number if the requested page
 * number in a paginated list is out of bounds
 */
class HttpSelfRedirect extends HttpRedirect
{
	/**
	 * @param array $queryParams
	 * @param string|null $fragment URL hash ('#...')
	 */
	public function __construct( $queryParams = [], $fragment = null )
	{
		parent::__construct(null, $queryParams, $fragment);
	}
}
