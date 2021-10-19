<?php
namespace XAF\web\outfilter;

use XAF\web\Response;

class ResponseFieldPassThroughFilter extends OutputFilter
{
	public function execute( Response $response )
	{
		$responseDataFieldKey = isset($this->params['field']) ? $this->params['field'] : 'response';
		$response->result = isset($response->data[$responseDataFieldKey]) ? $response->data[$responseDataFieldKey] : '';
	}
}

