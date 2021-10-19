<?php
namespace XAF\view\helper;

class HtmlSearchHighlightHelper
{
	/**
	 * @param string $text the already HTML-escaped (!) source
	 * @param string $pregPattern a preg pattern for the items to surround by search hit markers
	 * @return string
	 */
	public function putMarkers( $text, $pregPattern )
	{
		// We can't put HTML tags around the search hits now, because the text is unescaped and
		// will have to be HTML-escaped again, which would destroy the tags.
		// So instead, '{' and '}' are used as markers while any '{', '}' or '\' already existing
		// in the text will be backslash-escaped (and need to be unescaped later)
		return \preg_replace($pregPattern, '{$0}', \addcslashes($text, '\\{}'));
	}

	/**
	 * @param string $text the already HTML-escaped source with hit markers ('{' and '}') and optionally HTML-tags (<br>, links)
	 * @param string $startTag
	 * @param string $endTag
	 * @return string
	 */
	public function applyMarkers( $text, $startTag, $endTag )
	{
		$text = $this->stripMarkersFromHtmlTags($text);
		return \strtr(
			$text,
			[
				'\\{' => '{',
				'\\}' => '}',
				'{' => $startTag,
				'}' => $endTag,
				'\\\\' => '\\'
			]
		);
	}

	/**
	 * Strip search hit markers ('{' and '}') from within any HTML tags
	 *
	 * Markers may end up in href attributes of a tags when there are search hits in URLs which have been
	 * linkified
	 *
	 * @param string $text
	 * @return string
	 */
	private function stripMarkersFromHtmlTags( $text )
	{
		return \preg_replace_callback(
			'/<[^>]*[{}][^>]*>/u',
			function( $matches ) {
				// Die Ersetzungen von Escape-Sequenzen durch sich selbst verhindern, dass in den entsprechenden
				// Passagen geschweifte Klammern ersetzt werden (weil replace jede Stelle nur ein mal verarbeitet)
				return \strtr(
					$matches[0],
					[
						'\\\\' => '\\\\',
						'\\{' => '\\{',
						'\\}' => '\\}',
						'{' => '',
						'}' => ''
					]
				);
			},
			$text
		);
	}
}
