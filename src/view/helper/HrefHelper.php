<?php
namespace XAF\view\helper;

use XAF\web\UrlResolver;

class HrefHelper
{
	/** @var UrlResolver */
	private $urlResolver;

	public function __construct( UrlResolver $urlResolver )
	{
		$this->urlResolver = $urlResolver;
	}

	/**
	 * @return string
	 */
	public function getCurrentPagePath()
	{
		return $this->urlResolver->getCurrentPagePath();
	}

	/**
	 * @return array
	 */
	public function getCurrentQueryParams()
	{
		return $this->urlResolver->getCurrentQueryParams();
	}

	/**
	 * @return string
	 */
	public function getCurrentPagePathWithQuery()
	{
		return $this->urlResolver->getCurrentPagePathWithQuery();
	}

	/**
	 * @return array
	 */
	public function getAutoQueryParams()
	{
		return $this->urlResolver->getAutoQueryParams();
	}

	/**
	 * @param string $pagePath
	 * @param array $queryParams
	 * @return string
	 */
	public function getUrlPath( $pagePath, array $queryParams = [] )
	{
		return $this->urlResolver->buildUrlPath($pagePath, $queryParams);
	}

	/**
	 * @param string $pagePath
	 * @param array $queryParams
	 * @return string
	 */
	public function getHref( $pagePath, array $queryParams = [] )
	{
		return $this->urlResolver->buildHref($pagePath, $queryParams);
	}

	/**
	 * @param string $pagePath
	 * @param array $queryParams
	 * @return string
	 */
	public function getAbsoluteUrl( $pagePath, array $queryParams = [] )
	{
		return $this->urlResolver->buildAbsUrl($pagePath, $queryParams);
	}
}
