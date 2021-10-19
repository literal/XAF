<?php
namespace XAF\web\outfilter;

use XAF\web\Response;

class JsonEncoder extends OutputFilter
{
	public function execute( Response $response )
	{
		$response->result = \json_encode(
			$response->data,
			\JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_PRETTY_PRINT
		);
	}
}
