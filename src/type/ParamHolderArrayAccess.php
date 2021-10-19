<?php
namespace XAF\type;

use ArrayAccess;
use XAF\exception\SystemError;

/**
 * Wrap a param holder for read-only array access to a param holder
 *
 * Main use case: make the request vars param holder available in a view template.
 */
class ParamHolderArrayAccess implements ArrayAccess
{
	/** @var ParamHolder */
	private $params;

	public function __construct( ParamHolder $params )
	{
		$this->params = $params;
	}

	public function offsetExists( $offset )
	{
		return true;
	}

	public function offsetGet( $offset )
	{
		return $this->params->get($offset);
	}

	public function offsetSet( $offset, $value )
	{
		throw new SystemError('field cannot be set', $offset);
	}

	public function offsetUnset( $offset )
	{
		throw new SystemError('field cannot be unset', $offset);
	}
}
