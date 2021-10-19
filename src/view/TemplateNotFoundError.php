<?php
namespace XAF\view;

use XAF\exception\NotFoundError;

class TemplateNotFoundError extends NotFoundError
{
	public function __construct( $relatedValue = null, $details = null )
	{
		parent::__construct('template', $relatedValue, $details);
	}
}
