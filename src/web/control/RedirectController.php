<?php
namespace XAF\web\control;

use XAF\http\Request;
use XAF\web\exception\HttpRedirect;

/**
 * Standard controller for throwing an arbitrary HTTP redirect
 * Define in object map and use from routing table
 */
class RedirectController
{
	/** @var Request */
	private $request;

	public function __construct( Request $request )
	{
		$this->request = $request;
	}

	public function execute( $location, $keepQuery = false )
	{
		$query = $keepQuery ? $this->request->getQueryParams() : [];
		throw new HttpRedirect($location, $query);
	}
}
