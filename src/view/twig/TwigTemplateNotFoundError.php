<?php
namespace XAF\view\twig;

use Twig\Error\LoaderError;

/**
 * The loader must throw Twig\Error\LoaderError to make template fallback ("{% include ['a', 'b', 'c'] %}") work.
 *
 * So we inherit from the Twig exception class here and catch and rethrow as XAF error any exceptions not handled
 * within Twig itself.
 */
class TwigTemplateNotFoundError extends LoaderError
{
	/** @var string */
	private $originalMessage;

	/** @var mixed */
	private $originalValue;

	/** @var mixed */
	private $errorDetails;

	function __construct( $message, $value, $errorDetails = null )
	{
		parent::__construct($message . ': ' . $value);
		$this->errorDetails = $errorDetails;
	}

	/**
	 * @return string
	 */
	function getOriginalMessage()
	{
		return $this->originalMessage;
	}

	/**
	 * @return mixed
	 */
	function getOriginalValue()
	{
		return $this->originalValue;
	}

	/**
	 * @return mixed
	 */
	function getErrorDetails()
	{
		return $this->errorDetails;
	}
}
