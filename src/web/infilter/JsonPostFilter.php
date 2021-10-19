<?php
namespace XAF\web\infilter;

use XAF\http\Request;
use XAF\type\ParamHolder;
use XAF\web\exception\BadRequest;

/**
 * Create Form object from request data
 */
class JsonPostFilter extends InputFilter
{
	/** @var Request */
	private $request;

	/** @var ParamHolder */
	private $requestVars;

	/**
	 * @param Request $request
	 * @param ParamHolder $requestVars
	 */
	public function __construct( Request $request, ParamHolder $requestVars )
	{
		$this->request = $request;
		$this->requestVars = $requestVars;
		$this->setDefaultParams();
	}

	protected function setDefaultParams()
	{
		// Optional name of first level property to extract from the JSON object into the target var
		$this->setParam('extract', null);

		// default name of request var to store the received/extracted data in
		$this->setParam('targetVar', 'json');
	}

	public function execute()
	{
		$this->throwExceptionIfNotJsonRequest();
		$jsonSource = $this->request->getRawRequestBody();
		$result = $this->decodeAndExtract($jsonSource);
		$this->storeResultInRequestVar($result);
	}

	protected function throwExceptionIfNotJsonRequest()
	{
		if( !$this->isJsonRequest() )
		{
			throw new BadRequest('not a JSON request');
		}
	}

	/**
	 * @return bool
	 */
	protected function isJsonRequest()
	{
		$contentType = $this->request->getHeader('Content-Type');
		// There could be "; charset=..." after the type, so we only match the beginning
		return \strpos($contentType, 'application/json') === 0;
	}

	/**
	 * @param string $jsonSource
	 * @return mixed
	 */
	protected function decodeAndExtract( $jsonSource )
	{
		$result = @\json_decode($jsonSource, true);
		$this->throwExceptionIfJsonDecodingFailed($jsonSource);
		return $this->extractFieldFromResultIfRequested($result);
	}

	/**
	 * @param string $jsonSource
	 */
	protected function throwExceptionIfJsonDecodingFailed( $jsonSource )
	{
		$errorCode = \json_last_error();
		if( $errorCode != \JSON_ERROR_NONE )
		{
			throw new BadRequest('invalid JSON request body', $jsonSource, 'error code: ' . $errorCode);
		}
	}

	/**
	 * @param array $result
	 * @return mixed
	 */
	protected function extractFieldFromResultIfRequested( array $result )
	{
		$extractField = $this->getParam('extract');
		if( $extractField === null )
		{
			return $result;
		}

		if( !\array_key_exists($extractField, $result) )
		{
			throw new BadRequest('expected field missing in JSON request body', $extractField);
		}

		return $result[$extractField];
	}

	/**
	 * @param mixed $result
	 */
	protected function storeResultInRequestVar( $result )
	{
		$requestVarName = $this->getRequiredParam('targetVar');
		$this->requestVars->set($requestVarName, $result);
	}
}
