<?php
namespace XAF\web\session;

use XAF\helper\TokenGenerator;
use XAF\http\Request;
use XAF\web\session\Session;
use XAF\web\UrlResolver;

/**
 * Add a random token to the session params in the UrlResolver and check it on every request
 * to prevent cross site request forgery
 */
class CsrfProtector
{
	/** @var Session */
	protected $session;

	/** @var Request */
	protected $request;

	/** @var UrlResolver */
	protected $urlResolver;

	/** @var string Name of the Session field */
	protected $sessionFieldName = '_csrfProtectionToken';

	/** @var string Name of the query param */
	protected $queryParamName = 'ct';

	public function __construct( Session $session, Request $request, UrlResolver $urlResolver )
	{
		$this->session = $session;
		$this->request = $request;
		$this->urlResolver = $urlResolver;
	}

	/**
	 * @param string $name
	 */
	public function setQueryParamName( $name )
	{
		$this->queryParamName = $name;
	}

	public function start()
	{
		if( $this->session->isOpen() )
		{
			$this->initToken();
			$this->setQueryParam();
		}
	}

	protected function initToken()
	{
		$token = TokenGenerator::generateUrlSafeToken(12);
		$this->session->setData($this->sessionFieldName, $token);
	}

	protected function setQueryParam()
	{
		$token = $this->session->getData($this->sessionFieldName);
		$this->urlResolver->setAutoQueryParam($this->queryParamName, $token);
	}

	/**
	 * Check for correct received token and set the query param to carry the token forward
	 *
	 * @return bool Whether the correct token was received
	 */
	public function checkAndCarry()
	{
		if( $this->session->isOpen() )
		{
			$this->setQueryParam();
			return $this->wasCorrectTokenReceived();
		}
		return true;
	}

	/**
	 * @return bool
	 */
	protected function wasCorrectTokenReceived()
	{
		$expectedToken = $this->session->getData($this->sessionFieldName);
		$receivedToken = $this->request->getQueryParam($this->queryParamName);
		$this->request->unsetQueryParam($this->queryParamName);
		return $expectedToken === $receivedToken;
	}

	public function stop()
	{
		$this->urlResolver->setAutoQueryParam($this->queryParamName, null);
		if( $this->session->isOpen() )
		{
			$this->session->unsetData($this->sessionFieldName);
		}
	}
}
