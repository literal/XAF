<?php
namespace XAF\web\infilter;

use XAF\form\Form;

use XAF\type\ParamHolder;
use XAF\http\Request;
use XAF\di\DiContainer;

/**
 * Create Form object from request data
 */
class FormFilter extends InputFilter
{
	/** @var Request */
	private $request;

	/** @var ParamHolder */
	private $requestVars;

	/** @var DiContainer */
	private $diContainer;

	/** @var string|null */
	private $objectQualifier;

	/**
	 * @param Request $request
	 * @param ParamHolder $requestVars
	 * @param DiContainer $diContainer
	 * @param string|null $objectQualifier Usually the language tag written as an object qualifier, e. g. 'de.de'
	 */
	public function __construct( Request $request, ParamHolder $requestVars, DiContainer $diContainer, $objectQualifier )
	{
		$this->request = $request;
		$this->requestVars = $requestVars;
		$this->diContainer = $diContainer;
		$this->objectQualifier = $objectQualifier;
		$this->setDefaultParams();
	}

	protected function setDefaultParams()
	{
		// Default name of request var to store form object in, can be overridden with setParam('var', ...)
		$this->setParam('targetVar', 'form');

		// Default HTTP-method by which the form data is expected to be received, may be "POST", "GET" or "ANY".
		// In case of "ANY" POST data is preferred and GET data is used as a fallback only.
		$this->setParam('method', 'POST');
	}

	public function execute()
	{
		$form = $this->createFormObject();
		$this->populateFormFromRequest($form);
		$this->storeFormInRequestVar($form);
	}

	/**
	 * @return Form
	 */
	protected function createFormObject()
	{
		$formObjectKey = $this->getRequiredParam('formObject') .
			($this->objectQualifier ? '.' . $this->objectQualifier : '');
		return $this->diContainer->create($formObjectKey);
	}

	protected function populateFormFromRequest( Form $form )
	{
		$expectedRequestMethod = \strtoupper($this->getRequiredParam('method'));
		if( \in_array($expectedRequestMethod, ['POST', 'ANY']) && $this->request->getMethod() == 'POST' )
		{
			if( $form->importValues($this->request->getPostData()) )
			{
				$form->setReceived();
			}
		}
		else if( \in_array($expectedRequestMethod, ['GET', 'ANY']) )
		{
			if( $form->importValues($this->request->getQueryParams()) )
			{
				$form->setReceived();
			}
		}
	}

	protected function storeFormInRequestVar( Form $form )
	{
		$requestVarName = $this->getRequiredParam('targetVar');
		$this->requestVars->set($requestVarName, $form);
	}
}
