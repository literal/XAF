<?php

class DummyService
{
	public $constructorArgs = [];

	public function __construct()
	{
		$this->constructorArgs = func_get_args();
	}
}
