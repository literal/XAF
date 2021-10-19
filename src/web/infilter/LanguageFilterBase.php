<?php
namespace XAF\web\infilter;

use XAF\type\ParamHolder;
use XAF\web\UrlResolver;
use XAF\helper\LanguageMatcher;

abstract class LanguageFilterBase extends InputFilter
{
	/** @var ParamHolder */
	protected $requestVars;

	/** @var UrlResolver */
	protected $urlResolver;

	/** @var array */
	private $availableLanguages;

	public function __construct( ParamHolder $requestVars, UrlResolver $urlResolver, array $availableLanguages )
	{
		$this->requestVars = $requestVars;
		$this->urlResolver = $urlResolver;
		$this->availableLanguages = $availableLanguages;
		$this->setDefaultParams();
	}

	protected function setDefaultParams()
	{
		// Default name of the query param used for forwarding the langage in outgoing links, set empty to
		// prevent setting of the automatic param
		$this->setParam('queryParam', 'lang');

		// Default name of the request var to write the actual language tag to
		$this->setParam('targetVar', 'language');

		// Whether to set the query param even if there is just one language available
		$this->setParam('forceQueryParam', false);
	}

	/**
	 * @param array $preferredLanguages
	 * @return string|null
	 */
	protected function getBestLanguageMatch( array $preferredLanguages )
	{
		return $this->doLanguageChoicesExist()
			? LanguageMatcher::findBestAvailableLanguage($preferredLanguages, $this->availableLanguages)
			: $this->getDefaultLanguage();
	}

	/**
	 * @return bool
	 */
	protected function doLanguageChoicesExist()
	{
		return \sizeof($this->availableLanguages) > 1;
	}

	/**
	 * @return string|null
	 */
	protected function getDefaultLanguage()
	{
		return isset($this->availableLanguages[0]) ? $this->availableLanguages[0] : null;
	}

	/**
	 * @param string|null $languageTag
	 * @return bool
	 */
	protected function isAvailableLanguage( $languageTag )
	{
		return $languageTag !== null && \in_array($languageTag, $this->availableLanguages);
	}

	/**
	 * @param string $languageTag
	 */
	protected function setLanguage( $languageTag )
	{
		$this->setApplicationLanguage($languageTag);
		if( $this->shallQueryParamBeSet() )
		{
			$this->forwardLanguageQueryParam($languageTag);
		}
	}

	/**
	 * @param string|null $languageTag
	 */
	protected function setApplicationLanguage( $languageTag )
	{
		$this->requestVars->set($this->getParam('targetVar'), $languageTag);
	}

	/**
	 * @return bool
	 */
	protected function shallQueryParamBeSet()
	{
		return $this->doLanguageChoicesExist() || $this->getParam('forceQueryParam');
	}

	/**
	 * @param string|null $languageTag
	 */
	protected function forwardLanguageQueryParam( $languageTag )
	{
		$this->urlResolver->setAutoQueryParam($this->getParam('queryParam'), $languageTag);
	}
}
