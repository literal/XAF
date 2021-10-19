<?php
namespace XAF\web\outfilter;

use XAF\web\Response;

/**
 * Transforms rendered response into a series of JavaScript document.write() statements
 * Lines beeginning with ':' will be kept as literal Javascript
 */
class JsDocWriteFilter extends OutputFilter
{
	public function execute( Response $response )
	{
		$lines = \explode(\chr(10), \str_replace(\chr(13), '', $response->result));
		$result = [];
		foreach( $lines as $line )
		{
			$line = \trim($line);
			if( $line !== '' )
			{
				if( $line[0] == ':' )
				{
					$result[] = \substr($line, 1);
				}
				else
				{
					$result[] = 'document.write("' . \addcslashes($line, "\\\"\\'\r\n") . '\\n");';
				}
			}
		}
		$response->result = \implode("\n", $result);
	}
}
