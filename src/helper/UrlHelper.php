<?php
namespace XAF\helper;

class UrlHelper
{
	/**
	 * No instances allowed
	 */
	private function __construct() { }

	/**
	 * @param string $baseUrl Only used when $urlOrPath is not an absolute URL
	 * @param string $urlOrPath
	 * @return string
	 */
	static public function buildAbsoluteUrl( $baseUrl, $urlOrPath )
	{
		return self::isAbsoluteUrl($urlOrPath)
			? $urlOrPath
			: self::normalizeHostUrl($baseUrl) . \ltrim($urlOrPath, '/');
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	static public function isAbsoluteUrl( $path )
	{
		return \strpos($path, 'http://') === 0 || \strpos($path, 'https://') === 0;
	}

	/**
	 * @param string $url
	 * @return string
	 */
	static private function normalizeHostUrl( $url )
	{
		return \rtrim($url, '/') . '/';
	}

	/**
	 * Merge the query string contained in $path (if any) with the query params given in $params
	 * (which will, of course, be URL-escaped) and returns the resulting URL with query string
	 *
	 * Params with a null value (as opposed to empty string!) will be deleted from the query string.
	 *
	 * @param string $path
	 * @param array $params
	 * @return string
	 */
	static public function mergeQuery( $path, array $params )
	{
		$pathQueryPos = \strpos($path, '?');
		if( $pathQueryPos !== false )
		{
			$pathQuery = \substr($path, $pathQueryPos + 1);
			$pathParams = [];
			\parse_str($pathQuery, $pathParams);
			$params = \array_merge($pathParams, $params);
			$path = \substr($path, 0, $pathQueryPos);
		}
		return $path . self::buildQueryString($params);
	}

	/**
	 * @param array $params
	 * @return string
	 */
	static public function buildQueryString( array $params )
	{
		$fields = [];
		foreach( $params as $key => $value )
		{
			$fields = self::addToQueryFields($fields, $key, $value);
		}
		return $fields ? '?' . \implode('&', $fields) : '';
	}

	/**
	 * @param array $fields
	 * @param string $key
	 * @param mixed $value
	 * @return array
	 */
	static private function addToQueryFields( array $fields, $key, $value )
	{
		if( \is_scalar($value) && $value !== null && $value !== false )
		{
			$fields[] = \urlencode($key) . '=' . \urlencode($value);
		}
		else if( \is_array($value) )
		{
			foreach( $value as $elementKey => $elementValue )
			{
				$fields = self::addToQueryFields($fields, $key . '[' . $elementKey . ']', $elementValue);
			}
		}

		return $fields;
	}
}

