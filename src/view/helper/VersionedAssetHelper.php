<?php
namespace XAF\view\helper;

/**
 * Prepends a virtual version number path component before asset paths. This way assets can be set HTTP-cacheable
 * and new versions will be rolled out by changing the version parameter.
 *
 * The position of the version in the URL path ensures all relative references from the asset (like relative
 * image URLs in CSS files) are also affected when the version is changed.
 *
 * The web server must be configured to strip the version path component before mapping to a disk file.
 */
class VersionedAssetHelper implements AssetHelper
{
	/** @var string */
	private $rootUrl;

	public function __construct( $rootUrl = '' )
	{
		$this->rootUrl = \rtrim($rootUrl, '/');
	}

	public function getUrl( $path, $version = '0' )
	{
		return $this->rootUrl . '/' . $version . '/' . \ltrim($path, '/');
	}
}
