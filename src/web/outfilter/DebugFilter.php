<?php
namespace XAF\web\outfilter;

use XAF\type\ParamHolder;
use XAF\web\Response;

class DebugFilter extends OutputFilter
{
	/** @var ParamHolder */
	private $requestVars;

	public function __construct( ParamHolder $requestVars )
	{
		$this->requestVars = $requestVars;
	}

	public function execute( Response $response )
	{
		$response->result .=
			"\r\n" .
			'<!--' . "\r\n" .
			"\r\n" .
			'******* TEMPLATE CONTEXT DATA: *******' . "\r\n" .
			$this->formatDebugInfo($response->data) .
			"\r\n" .
			'******* REQUEST VARS: *******' . "\r\n" .
			$this->formatDebugInfo($this->requestVars) .
			"\r\n" . '-->';
	}

	protected function formatDebugInfo( $debugInfo )
	{
		// replace second dash with endash, or else Firefox will believe the comment is finished even without a '>' (!)
		return \str_replace('--', '-–', \print_r($debugInfo, true));
	}
}
