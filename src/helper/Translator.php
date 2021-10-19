<?php
namespace XAF\helper;

use XAF\helper\LanguageTagHelper;
use XAF\helper\MessageRenderer;

/**
 * Look up message by a key in language specific message table with optional
 * inclusion of message params in result
 *
 * Params are included in messages by '%paramName%' placeholders.
 * The escape sequence for a literal percent sign is '%%'
 */
class Translator
{
	const FINAL_EXTENSION = 'php';

	/** @var array Paths in which to look for a request mesage table */
	private $messageTablePaths = [];

	/** @var array Contents of the loaded tables (indexed by table key, language tag, then message key) */
	private $messageTables = [];

	/** @var array Full paths of the loaded table files for error reporting - indexed by table key */
	private $tableSources = [];

	/**
	 * @param array|string $messageTablePaths if an array, all given paths will be searched for a mesage table
	 */
	public function __construct( $messageTablePaths )
	{
		foreach( (array)$messageTablePaths as $messageTablePath )
		{
			$this->messageTablePaths[] = $this->normalizePath($messageTablePath);
		}
	}

	/**
	 * @param string $path
	 * @return string
	 */
	private function normalizePath( $path )
	{
		return \rtrim($path, '\\/') . '/';
	}

	/**
	 * @param string $messageKey
	 * @param string $tableKey
	 * @param string $languageTag The target language
	 * @param array|null $params
	 * @return string
	 */
	public function translate( $messageKey, $tableKey, $languageTag, $params = null )
	{
		$params = \is_array($params) ? $params : [];

		$mesageTable = $this->getMessageTable($tableKey, $languageTag);

		// Message table does not exist: Render the message key.
		if( $mesageTable === false )
		{
			return MessageRenderer::render($messageKey, $params);
		}

		// Message table exists, but requested message key not found: Issue warning and render the message key.
		if( !isset($mesageTable[$messageKey]) )
		{
			$this->issueWarning(
				'Unknown message key [' . $messageKey . '] in ' . $this->tableSources[$tableKey][$languageTag]
			);
			return MessageRenderer::render($messageKey, $params);
		}

		$result = MessageRenderer::render($mesageTable[$messageKey], $params);
		if( $result === null )
		{
			$this->issueWarning(
				'Invalid entry for key [' . $messageKey . '] in ' . $this->tableSources[$tableKey][$languageTag]
			);
			return $messageKey;
		}

		return $result;
	}

	/**
	 * @param string $tableKey
	 * @param string $languageTag
	 * @return array|false
	 */
	private function getMessageTable( $tableKey, $languageTag )
	{
		if( isset($this->messageTables[$tableKey][$languageTag]) )
		{
			return $this->messageTables[$tableKey][$languageTag];
		}

		foreach( $this->messageTablePaths as $path )
		{
			foreach( $this->getExtensionCandidatesForLanguageTag($languageTag) as $extensionCandidate )
			{
				$candidate = $path . $tableKey . $extensionCandidate;
				if( \file_exists($candidate) )
				{
					$this->messageTables[$tableKey][$languageTag] = $this->loadMessageTable($candidate);
					$this->tableSources[$tableKey][$languageTag] = $candidate;
					return $this->messageTables[$tableKey][$languageTag];
				}
			}
		}

		// To remember there is no such table
		$this->messageTables[$tableKey][$languageTag] = false;
		return false;
	}

	/**
	 * @param string $languageTag
	 * @return array The file name extensions to scan for - from most to least specific,
	 *     e.g. ['.de.de.php', '.de.php', '.php']
	 */
	private function getExtensionCandidatesForLanguageTag( $languageTag )
	{
		$result = [];
		if( $languageTag )
		{
			$languageTagParts = LanguageTagHelper::split($languageTag);
			while( $languageTagParts )
			{
				$result[] = '.' . \implode('.', $languageTagParts) . '.' . self::FINAL_EXTENSION;
				\array_pop($languageTagParts);
			}
		}
		$result[] = '.' . self::FINAL_EXTENSION;
		return $result;
	}

	/**
	 * @param string $filename
	 * @return array
	 */
	private function loadMessageTable( $filename )
	{
		$tableContents = require $filename;
		if( !\is_array($tableContents) )
		{
			$this->issueWarning('Message table file does not return an array: ' . $filename);
			return [];
		}
		return $tableContents;
	}

	/**
	 * @param string $message
	 */
	private function issueWarning( $message )
	{
		\trigger_error('Translator: ' . $message, \E_USER_WARNING);
	}
}
