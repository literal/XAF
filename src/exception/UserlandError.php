<?php
namespace XAF\exception;

use Exception;

/**
 * Extend this class for errors that are thrown to break the current processing and
 * generate an error message/page for the user.
 *
 * A UserlandError shall not be logged and not quit the application (i.e. final processing like
 * writing modified data to a DB shall be carried out as usual).
 *
 * The "view context" is a simple hash that contains the information to be presented to the user.
 * In a web app it would be made available to the error rendering template.
 */
abstract class UserlandError extends Exception
{
	/** @var array */
	protected $viewContext;

	/**
	 * @param array $viewContext Hash of information to display to the user (template context in a web app)
	 * @param string $message 
	 */
	public function __construct( array $viewContext = [], $message = 'userland error' )
	{
		// message should not matter: A userland error should *always* be caught inside the application
		// and be handled by displaying a page/message to the user
		parent::__construct($message);
		$this->setViewContext($viewContext);
	}

	/**
	 * Set/replace the complete view context hash at once
	 *
	 * @param array $context
	 */
	public function setViewContext( array $context )
	{
		$this->viewContext = $context;
	}

	/**
	 * Set/add a single field in the view context hash
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function setViewContextField( $key, $value )
	{
		$this->viewContext[$key] = $value;
	}

	/**
	 * @return array
	 */
	public function getViewContext()
	{
		return $this->viewContext;
	}
}

