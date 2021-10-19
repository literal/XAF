<?php
namespace XAF\view\helper;

/**
 * Resolve an internal web asset (JS, stylesheet, image etc.) path to a (relative or absolte URL for using
 * in HTML tags (e. g. for a "src=..." attribute)
 */
interface AssetHelper
{
	/**
	 * @param string $path
	 * @return string
	 */
	public function getUrl( $path );
}
