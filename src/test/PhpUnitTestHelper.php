<?php
namespace XAF\test;

class PhpUnitTestHelper
{
	/**
	 * @param callable $function
	 */
	static public function runWithWarningsDisabled( callable $function )
	{
		@$function();
	}
}
