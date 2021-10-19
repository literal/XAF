<?php
namespace XAF\view\helper;

use XAF\helper\UrlHelper as BaseUrlHelper;

/**
 * Provides URL operations independently of a web application context, i.e. there is no dependency on
 * such thngs as a current host, request path, session query params etc.
 */
class UrlHelper
{
	/**
	 * Add query string to URL or URL-path and merge it ith already existing query in URL
	 *
	 * @param string $urlOrPath
	 * @param array $queryParams
	 * @return string
	 */
	public function addQueryParams( $urlOrPath, array $queryParams = [] )
	{
		return $queryParams ? BaseUrlHelper::mergeQuery($urlOrPath, $queryParams) : $urlOrPath;
	}

	/**
	 * @param string $baseUrl Only used when $urlOrPath is not an absolute URL
	 * @param string $urlOrPath
	 * @return string
	 */
	public function buildAbsoluteUrl( $baseUrl, $urlOrPath )
	{
		return BaseUrlHelper::buildAbsoluteUrl($baseUrl, $urlOrPath);
	}
}
