<?php
namespace XAF\web\infilter;

use XAF\web\session\SessionHandler;

/**
 * Auto-start or continue a session
 */
class SessionFilter extends InputFilter
{
	/** @var SessionHandler */
	private $sessionHandler;

	public function __construct( SessionHandler $sessionHandler )
	{
		$this->sessionHandler = $sessionHandler;
		$this->setDefaultParams();
	}

	protected function setDefaultParams()
	{
		$this->setParam('autostart', true);
	}

	public function execute()
	{
		$doAutostart = $this->getRequiredParam('autostart');

		if( $doAutostart )
		{
			$this->sessionHandler->continueOrStartSession();
		}
		else
		{
			$this->sessionHandler->continueSessionIfExists();
		}
	}
}
