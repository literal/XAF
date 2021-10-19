<?php
namespace XAF\web\outfilter;

use XAF\web\Response;

/**
 * For adding arbitrary elements to the response data by calling setParam().
 *
 * Mostly used in combination object map entries that contain a creator function which creates a
 * ContextAppendFilter, sets some param(s) and thus allows to make data available to templates
 * by calling the filter in the request routing table.
 */
class ContextAppendFilter extends OutputFilter
{
	public function execute( Response $response )
	{
		foreach( $this->params as $key => $value )
		{
			$response->data[$key] = $value;
		}
	}
}
