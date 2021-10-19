<?php
namespace XAF\web;

use XAF\http\Request;

class Application extends \XAF\Application
{
	protected function execute()
	{
		$frontController = $this->diContainer->get('FrontController'); /* @var $frontController FrontController */
		$request = $this->diContainer->get('Request'); /* @var $request Request */
		$frontController->handleHttpRequest($request->getMethod(), $request->getRequestPath());
	}
}
