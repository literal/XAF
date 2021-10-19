<?php
namespace XAF\web\infilter;

use XAF\web\session\CsrfProtector;
use XAF\web\exception\HttpRedirect;

class CsrfProtectionFilter extends InputFilter
{
	/** @var CsrfProtector */
	private $csrfProtector;

	public function __construct( CsrfProtector $csrfProtector )
	{
		$this->csrfProtector = $csrfProtector;
		$this->setParam('redirectUrl', '/');
	}

	public function execute()
	{
		if( !$this->csrfProtector->checkAndCarry() )
		{
			throw new HttpRedirect($this->getParam('redirectUrl'));
		}
	}
}
