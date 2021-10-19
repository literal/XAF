<?php
namespace XAF\web;

interface UrlResolver
{
	/**
	 * Set a name/value pair that needs to be added to the query string of all link URLs that
	 * point to other pages.
	 *
	 * Used for e.g.:
	 * - a session ID param in case a client does not support cookies
	 * - a language code to be carried along from page to page
	 *
	 * @param string $name
	 * @param string|null $value Set null to remove param
	 */
	public function setAutoQueryParam( $name, $value );

	/**
	 * Get all params set through setAutoQueryParam().
	 *
	 * The typical use case would be an HTML form using the GET method for whose action attribute
	 * buildUrlPath() would be called without query params (because a GET form can have no query string
	 * in the action) and any auto params would have to be carried forward through hidden form fields.
	 *
	 * @return array {<name> => <value>, ...}
	 */
	public function getAutoQueryParams();

	/**
	 * Base URL consists of protocol, host port (if non-standard) and a trailing slash
	 *
	 * @return string E.g. 'http://www.domain.com/'
	 */
	public function getBaseUrl();

	/**
	 * The root path is common to *all* URL paths of the app. It is stripped from the
	 * beginning of the request path before routing and is prepended to paths when building outgoing
	 * URLs/hrefs.
	 *
	 * If the app runs from the document root of a (virtual) host, the root path will be ''.
	 *
	 * @return string root path with leading slash or empty string, e.g. '/myapp' or just ''
	 */
	public function getRootPath();

	/**
	 * The base path is a path common to the current application module.
	 *
	 * It will be prepended before all relative URL paths.
	 *
	 * The rationale behind this: if '/forum' is set as base path for all request URIs
	 * starting with '/rootpath/forum', a controller handling '/rootpath/forum/posts/33'
	 * can link or redirect to 'boards/11', resulting in a new URL path of '/rootpath/forum/boards/11'.
	 * Thus, a moving of the whole forum module to a different URI path
	 * (e.g. '/rootpath/community/myforum/...') can be done solely by changing the base path.
	 *
	 * @return string base path with leading slash, e.g. '/forum' - or empty string if no base path defined
	 */
	public function getBasePath();

	/**
	 * Get "internal" path for currently requested page. That is, the  current request path (URL path)
	 * with the root path stipped from the beginning (if any) and no query string.
	 *
	 * @return string
	 */
	public function getCurrentPagePath();

	/**
	 * Get a hash of query params passed in the current HTTP request.
	 *
	 * @return array {<name> => <value>, ...}
	 */
	public function getCurrentQueryParams();

	/**
	 * Same as getCurrentPagePath but adds current query string (if any)
	 *
	 * @return string
	 */
	public function getCurrentPagePathWithQuery();

	/**
	 * Get URL path for a page of the application
	 *
	 * * Results for the different types of input paths:
	 * - '.' or ''     : '/rootpath/basepath' (basepath without trailing slash)
	 * - './'          : '/rootpath/basepath/' (basepath with trailing slash)
	 * - 'some/path'   : '/rootpath/basepath/some/path' (relative to base path)
	 * - './some/path' : (same as previous)
	 * - '/some/path'  : '/rootpath/some/path' (absolute, or rather, relative to root path)
	 *
	 * @param string $pagePath
	 * @param array $params query string params {<name>: <value>, ...} - will be URL-encoded
	 * @return string
	 */
	public function buildUrlPath( $pagePath, array $params = [] );

	/**
	 * Same as buildUrlPath(), but will return a fully qualified URL with protocol and hostname.
	 *
	 * @param string $pagePath
	 * @param array $params query string params {<name>: <value>, ...} - will be URL-encoded
	 * @return string
	 */
	public function buildAbsUrl( $pagePath, array $params = [] );

	/**
	 * Like buildUrlPath(), but auto query params will be added if present.
	 *
	 * Use this method for an HTML link to another page or for the action attribute of
	 * a POST method HTML form.
	 *
	 * @param string $pagePath
	 * @param array $params query string params {<name>: <value>, ...} - will be URL-encoded
	 * @return string
	 */
	public function buildHref( $pagePath, array $params = [] );

	/**
	 * Same as buildHref(), but will return a fully qualified URL with protocol and hostname.
	 *
	 * @param string $pagePath
	 * @param array $params query string params {<name>: <value>, ...} - will be URL-encoded
	 * @return string
	 */
	public function buildAbsHref( $pagePath, array $params = [] );

	/**
	 * Extract internal page path from a raw request URL path, i.e. strip the root path from the beginning
	 * of $urlPath (if a root path is defined).
	 *
	 * @param string $urlPath
	 * @return string
	 */
	public function urlPathToPagePath( $urlPath );
}
