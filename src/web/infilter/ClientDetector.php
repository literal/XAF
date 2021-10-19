<?php
namespace XAF\web\infilter;

use XAF\http\Request;
use XAF\type\ParamHolder;

class ClientDetector extends InputFilter
{
	/** @var Request */
	private $request;

	/** @var ParamHolder */
	private $requestVars;

	public function __construct( Request $request, ParamHolder $requestVars )
	{
		$this->request = $request;
		$this->requestVars = $requestVars;
		$this->setDefaultParams();
	}

	protected function setDefaultParams()
	{
		$this->setParam('isAndroidTargetVar', 'isAndroidClient');
		$this->setParam('isIosTargetVar', 'isIosClient');
		$this->setParam('iosDeviceTypeVar', 'iosDeviceType');
	}

	public function execute()
	{
		$userAgent = $this->request->getUserAgent();

		$this->requestVars->set(
			$this->getParam('isAndroidTargetVar'),
			(bool)\preg_match('/\\bLinux\\b.*\\bAndroid\\b/', $userAgent)
		);

		if( \preg_match('/\\b(iPod|iPad|iPhone)\\b.*\\bAppleWebKit\\b/', $userAgent, $matches) )
		{
			$this->requestVars->set($this->getParam('isIosTargetVar'), true);
			$this->requestVars->set($this->getParam('iosDeviceTypeVar'), $matches[1]);
		}
		else
		{
			$this->requestVars->set($this->getParam('isIosTargetVar'), false);
		}
	}
}