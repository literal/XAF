<?php
namespace XAF\web\exception;

/**
 * Thrown when a mandatory query parameter or POST field is missing or does not have the expected format.
 */
class RequestFieldError extends BadRequest
{
	public function __construct( $fieldKey, $value = null, $details = null )
	{
		parent::__construct('request field validation failed for \'' . $fieldKey . '\'', $value, $details);
		$this->addDebugInfo('field key', $fieldKey);
	}
}
