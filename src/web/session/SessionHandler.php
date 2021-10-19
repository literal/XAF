<?php
namespace XAF\web\session;

use XAF\http\Request;

/**
 * Manage current session and corresponding session token cookie or query param
 */
abstract class SessionHandler
{
	/** @var Session */
	protected $session;

	/** @var Request */
	protected $request;

	/** @var string */
	protected $propagationFieldName = 'st';

	public function __construct( Session $session, Request $request )
	{
		$this->session = $session;
		$this->request = $request;
	}

	/**
	 * @param string $fieldName Name of the cookie or query param to carry the session token
	 */
	public function setPropagationFieldName( $fieldName )
	{
		$this->propagationFieldName = $fieldName;
	}

	public function startSession()
	{
		if( $this->session->isOpen() )
		{
			$this->session->end();
		}
		$this->session->start();
		$this->propagateSessionToken();
	}

	public function continueOrStartSession()
	{
		$this->continueSessionIfExists();
		if( !$this->session->isOpen() )
		{
			$this->startSession();
		}
	}

	public function continueSessionIfExists()
	{
		if( !$this->session->isOpen() )
		{
			$token = $this->fetchSessionToken();
			if( $token )
			{
				$this->session->continueIfExists($token);
				if( $this->session->isOpen() )
				{
					$this->propagateSessionToken();
				}
			}
		}
	}


	public function endSession()
	{
		$this->session->end();
		$this->removeSessionToken();
	}

	abstract protected function propagateSessionToken();

	/**
	 * @return string|null
	 */
	abstract protected function fetchSessionToken();

	abstract protected function removeSessionToken();
}
