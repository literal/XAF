<?php
namespace XAF\web\infilter;

use XAF\http\Request;
use XAF\type\ParamHolder;
use XAF\web\UrlResolver;
use XAF\http\AcceptHeaderParser;
use XAF\web\exception\HttpSelfRedirect;

/**
 * This filter attempts to read the language tag from a query string parameter.
 *
 * If the query parameter is not present or contains no valid language tag, an auto-detect from the client's
 * HTTP "Accept-Language"-header is performed.
 *
 * The resulting language tag is set as an auto-param in the UrlResolver to carry it forward in all outgoing links.
 */
class QueryStringLanguageFilter extends LanguageFilterBase
{
	/** @var Request */
	private $request;

	public function __construct(
		Request $request,
		ParamHolder $requestVars,
		UrlResolver $urlResolver,
		array $availableLanguages
	)
	{
		$this->request = $request;
		parent::__construct($requestVars, $urlResolver, $availableLanguages);
	}

	protected function setDefaultParams()
	{
		parent::setDefaultParams();

		// Whether to issue an HTTP redirect for request without a valid language tag (when a query param is required
		// at all, i.e. when there are multiple languages available or the param 'forceQueryParam' is set)
		$this->setParam('redirect', true);
	}

	public function execute()
	{
		if( $this->doLanguageChoicesExist() )
		{
			$this->setLanguage($this->getLanguageFromQueryParam() ?: $this->getLanguageFromAcceptHeader());
		}
		else
		{
			$this->setLanguage($this->getDefaultLanguage());
		}
	}

	/**
	 * @return string|null
	 */
	private function getLanguageFromQueryParam()
	{
		$result = $this->request->getQueryParam($this->getParam('queryParam'));
		return $this->isAvailableLanguage($result) ? $result : null;
	}

	/**
	 * @return string|null
	 */
	private function getLanguageFromAcceptHeader()
	{
		$clientLanguagePreferences = $this->getLanguagePreferencesFromAcceptHeader();
		return $this->getBestLanguageMatch($clientLanguagePreferences);
	}

	/**
	 * @return array
	 */
	private function getLanguagePreferencesFromAcceptHeader()
	{
		$acceptLanuageHeader = $this->request->getHeader('Accept-Language');
		return isset($acceptLanuageHeader)
			? AcceptHeaderParser::parse($acceptLanuageHeader)
			: [];
	}

	/**
	 * @param string $languageTag
	 */
	protected function forwardLanguageQueryParam( $languageTag )
	{
		parent::forwardLanguageQueryParam($languageTag);
		if( $this->getParam('redirect') && $languageTag != $this->getLanguageFromQueryParam() )
		{
			throw new HttpSelfRedirect();
		}
	}
}
