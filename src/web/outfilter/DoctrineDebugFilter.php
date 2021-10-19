<?php
namespace XAF\web\outfilter;

use Doctrine\DBAL\Logging\DebugStack;
use XAF\web\Response;

class DoctrineDebugFilter
{
	/** @var DebugStack */
	private $debugStack;

	public function __construct( DebugStack $debugStack )
	{
		$this->debugStack = $debugStack;
	}

	public function execute( Response $response )
	{
		$response->result .=
			"\r\n" .
			'<!--' . "\r\n" .
			"\r\n" .
			'******* SQL STATEMENTS ISSUED BY DOCTRINE: *******' . "\r\n" .
			$this->formatDebugInfo($this->debugStack->queries) .
			"\r\n" . '-->';
	}

	protected function formatDebugInfo( $debugInfo )
	{
		// replace second dash with endash, or else Firefox will believe the comment is finished even without a '>' (!)
		return \str_replace('--', '-–', \print_r($debugInfo, true));

	}
}

