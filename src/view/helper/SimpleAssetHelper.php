<?php
namespace XAF\view\helper;

class SimpleAssetHelper implements AssetHelper
{
	/** @var string */
	private $rootUrl;

	public function __construct( $rootUrl = '' )
	{
		$this->rootUrl = \rtrim($rootUrl, '/');
	}

	public function getUrl( $path )
	{
		return $this->rootUrl . '/' . \ltrim($path, '/');
	}
}
