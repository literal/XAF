<?php
namespace XAF\web;

class CookieSetter
{
	/** @var UrlResolver */
	private $urlResolver;

	public function __construct( UrlResolver $urlResolver )
	{
		$this->urlResolver = $urlResolver;
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param int $expiryTs Unix timestamp for expiry, 0 for session cookies (that expire when the browser is closed)
	 * @param type $path Root path - a page path relative to the current root URL path
	 */
	public function setCookie( $name, $value, $expiryTs, $path = '/' )
	{
		$cookiePath = $this->urlResolver->buildUrlPath('/');
		\setcookie($name, $value, $expiryTs, $cookiePath);
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param type $path Root path - a page path relative to the current root URL path
	 */
	public function setSessionCookie( $name, $value, $path = '/' )
	{
		$this->setCookie($name, $value, 0, $path);
	}

	/**
	 * @param string $name
	 * @param string $path
	 */
	public function deleteCookie( $name, $path = '/' )
	{
		// Timestamp is 2000-01-01 00:00:00 UTC
		$this->setCookie($name, '', 946684800, $path);
	}
}
