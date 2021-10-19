<?php
namespace XAF\web\outfilter;

use XAF\web\Response;

class ResponseFieldPassThroughFilter extends OutputFilter
{
	public function execute( Response $response )
	{
		$responseDataFieldKey = $this->params['field'] ?? 'response';
		$response->result = $response->data[$responseDataFieldKey] ?? '';
	}
}

