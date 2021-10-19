<?php
namespace DummyNamespace;

class DummyServiceWithNamespace
{
	public $constructorArgs = [];

	public function __construct()
	{
		$this->constructorArgs = \func_get_args();
	}
}
