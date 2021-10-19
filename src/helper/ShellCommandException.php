<?php
namespace XAF\helper;

use XAF\exception\ExtendedError;

class ShellCommandException extends ExtendedError
{
	public function getLogClass()
	{
		return 'process runner error';
	}
}
