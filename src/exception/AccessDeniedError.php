<?php
namespace XAF\exception;

/**
 * Thrown whenever access to resource is not allowed
 */
class AccessDeniedError extends ValueRelatedError
{
	/**
	 * @param string $subject the kind of resource to which access was denied
	 * @param mixed $relatedValue if applicable, the ID, path etc. of the resource to which access was denied
	 * @param string|null $details
	 */
	public function __construct( $subject, $relatedValue = null, $details = null )
	{
		parent::__construct('access to ' . $subject . ' denied', $relatedValue, $details);
	}

	/**
	 * @return string
	 */
	public function getLogClass()
	{
		return 'access denied';
	}

}
