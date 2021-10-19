<?php
namespace XAF\web\exception;

use Exception;

/**
 * Not an error but thrown to trigger an internal redirect, i. e. another routing pass
 */
class InternalRedirect extends Exception
{
	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @param string $path request path to redirect to, usually starts with '@' to mark an internal-only location in the routing table
	 */
	public function __construct( $path )
	{
		$this->path = $path;
		parent::__construct('internal redirect to ' . $path);
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}
}
