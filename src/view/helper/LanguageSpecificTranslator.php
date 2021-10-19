<?php
namespace XAF\view\helper;

use XAF\helper\Translator;

/**
 * Facade for a translator, fixed to a particular language
 */
class LanguageSpecificTranslator
{
	/** @var Translator */
	private $translator;

	/** @var string */
	private $languageTag;

	/**
	 * @param Translator $translator
	 * @param string|null $languageTag
	 */
	public function __construct( Translator $translator, $languageTag )
	{
		$this->translator = $translator;
		$this->languageTag = $languageTag;
	}

	/**
	 * @param string $messageKey
	 * @param string $tableKey
	 * @param mixed $params
	 * @return string
	 */
	public function translate( $messageKey, $tableKey, $params = null )
	{
		return $this->translator->translate($messageKey, $tableKey, $this->languageTag, $params);
	}
}
