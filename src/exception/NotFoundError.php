<?php
namespace XAF\exception;

/**
 * Thrown whenever a resource could not be found. 
 */
class NotFoundError extends ValueRelatedError
{
	/**
	 * @param string $subject description of WHAT has not been found
	 * @param mixed $relatedValue if applicable, the ID, path etc. that was requested and could not be resolved
	 * @param mixed $details
	 */
	public function __construct( $subject, $relatedValue = null, $details = null )
	{
		parent::__construct($subject . ' not found', $relatedValue, $details);
	}

	/**
	 * @return string
	 */
	public function getLogClass()
	{
		return 'not found';
	}
}
