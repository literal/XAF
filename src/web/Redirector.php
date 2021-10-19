<?php
namespace XAF\web;

use XAF\web\UrlResolver;
use XAF\http\Request;
use XAF\helper\UrlHelper;

class Redirector
{
	/** @var UrlResolver */
	protected $urlResolver;

	/** @var Request */
	protected $request;

	public function __construct( UrlResolver $urlResolver, Request $request )
	{
		$this->urlResolver = $urlResolver;
		$this->request = $request;
	}

	/**
	 * @param string|null $path current request path if null, else absolute or relative to current URL resolver base path
	 * @param array $queryParams
	 * @param string|null $fragment
	 */
	public function redirect( $path = null, $queryParams = [], $fragment = null )
	{
		$this->discardAllOutputBuffers();
		$absoluteUrl = $this->composeAbsoluteUrl($path, $queryParams, $fragment);
		$this->sendRedirectResponse($absoluteUrl);
	}

	protected function discardAllOutputBuffers()
	{
		while( \ob_get_level() > 0 )
		{
			\ob_end_clean();
		}
	}

	/**
	 * @param string|null $urlOrPath
	 * @param array $queryParams
	 * @param string|null $fragment
	 * @return string
	 */
	protected function composeAbsoluteUrl( $urlOrPath, array $queryParams, $fragment )
	{
		if( $urlOrPath === null )
		{
			$urlOrPath = $this->urlResolver->getCurrentPagePathWithQuery();
		}

		if( UrlHelper::isAbsoluteUrl($urlOrPath) )
		{
			$url = UrlHelper::mergeQuery($urlOrPath, $queryParams);
		}
		else
		{
			$url = $this->urlResolver->buildAbsHref($urlOrPath, $queryParams);
		}

		if( $fragment !== null )
		{
			$url .= '#' . \urlencode($fragment);
		}

		return $url;
	}

	/**
	 * @param string $absoluteUrl
	 */
	protected function sendRedirectResponse( $absoluteUrl )
	{
		/*
		// Enable this for testing redirects
		echo '<a href="' . \htmlspecialchars($absoluteUrl) . '">' . \htmlspecialchars($absoluteUrl) . '</a>';
		return;
		/**/

		// Although most browsers don't do that, the HTTP spec says that for a 302 redirect the same request
		// method shall be used as for the original request, while a 303 tells the browser to always use GET.
		// In practice we never want a POST -> POST redirect (and most browsers don't support it anyway).
		\header('Location: ' . $absoluteUrl, true, $this->isPostRequest() ? 303 : 302);
		\header('Content-Type: text/html; charset=utf-8');
		echo '<html><head>' .
			'<meta http-equiv="refresh" content="0;url=' . \htmlspecialchars($absoluteUrl) . '">' .
			'</head></html>';
	}

	/**
	 * @return bool
	 */
	private function isPostRequest()
	{
		return $this->request->getMethod() == 'POST';
	}
}
