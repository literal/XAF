<?php
namespace XAF\web\outfilter;

use XAF\web\Response;

class EchoFilter extends OutputFilter
{
	public function execute( Response $response )
	{
		\header('Content-Length: ' . \strlen($response->result));
		echo $response->result;
	}
}
