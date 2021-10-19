<?php
namespace XAF\web\session;

use XAF\http\Request;
use XAF\web\CookieSetter;

class CookieSessionHandler extends SessionHandler
{
	/** @var CookieSetter */
	private $cookieSetter;

	public function __construct( Session $session, Request $request, CookieSetter $cookieSetter )
	{
		parent::__construct($session, $request);
		$this->cookieSetter = $cookieSetter;
	}

	protected function propagateSessionToken()
	{
		// Cookie only needs to be set once when the session is created
		if( $this->session->isNew() )
		{
			$this->cookieSetter->setSessionCookie($this->propagationFieldName, $this->session->getToken());
		}
	}

	/**
	 * @return string|null
	 */
	protected function fetchSessionToken()
	{
		return $this->request->getCookie($this->propagationFieldName);
	}

	protected function removeSessionToken()
	{
		$this->cookieSetter->deleteCookie($this->propagationFieldName);
	}
}
