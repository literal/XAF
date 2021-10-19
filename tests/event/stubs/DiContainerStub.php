<?php
namespace XAF\event;

use XAF\di\Factory;

class DiContainerStub implements \XAF\di\DiContainer
{
	private $handlerMock;

	public function __construct( $handlerMock )
	{
		$this->handlerMock = $handlerMock;
	}

	public function setFactory( Factory $factory ) {}
	public function createChildContainer() {}
	public function set( $key, $object ) {}
	public function getLocal( $key ) {}
	public function create( $key ) {}
	public function createLocal( $key ) {}
	public function getAllLocalObjectAliases() {}
	public function isKnown( $key ) {}
	public function isKnownLocally( $key ) {}
	public function existsLocally( $key ) {}

	public function get( $key )
	{
		return $this->handlerMock;
	}

	public function exists( $key )
	{
		return $key != 'missingObject';
	}

}
