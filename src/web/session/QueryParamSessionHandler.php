<?php
namespace XAF\web\session;

use XAF\http\Request;
use XAF\web\UrlResolver;

class QueryParamSessionHandler extends SessionHandler
{
	/** @var UrlResolver */
	private $urlResolver;

	public function __construct( Session $session, Request $request, UrlResolver $urlResolver )
	{
		parent::__construct($session, $request);
		$this->urlResolver = $urlResolver;
	}

	protected function propagateSessionToken()
	{
		$this->urlResolver->setAutoQueryParam($this->propagationFieldName, $this->session->getToken());
	}

	/**
	 * @return string|null
	 */
	protected function fetchSessionToken()
	{
		return $this->request->getQueryParam($this->propagationFieldName);
	}

	protected function removeSessionToken()
	{
		$this->urlResolver->setAutoQueryParam($this->propagationFieldName, null);
	}
}
