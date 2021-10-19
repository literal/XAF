<?php
namespace XAF\markdown;

/**
 * Loosely based on html2text, see below
 *
 * @todo Ordered list and definition list support
 *
 * @todo Not specific to markdown, move to different namespace
 */

/* * ****************************************************************************
 * Copyright (c) 2010 Jevon Wright and others.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors:
 *    Jevon Wright - initial API and implementation
 * ************************************************************************** */

use DOMDocument;
use DOMDocumentType;
use DOMNode;
use DOMElement;
use DOMText;

use XAF\exception\SystemError;

class HtmlToPlaintextConverter
{
	/**
	 * @param html the input HTML
	 * @return the HTML converted, as best as possible, to text
	 */
	public function convert( $html )
	{
		if( \trim($html) === '' )
		{
			return '';
		}

		$html = $this->fixNewlines($html);

		// Fix to prevent DOM from treating input as latin1 encoded
		if( \strpos($html, '<?xml ') === false )
		{
			$html = '<?xml encoding="UTF-8">' . $html;
		}

		$doc = new DOMDocument();
		if( !$doc->loadHTML($html) )
		{
			throw new SystemError('Could not load HTML - badly formed?', $html);
		}

		$result = $this->parseNode($doc);

		// remove leading spaces on each line, leaving inserted tabs at beginning of line alone
		// (all tabs in source document should have been replaced by single spaces beforehand, so all tabs
		// are intentional indents)
		$result = \preg_replace("/^ *(\t*) */m", '$1', $result);
		// remove trailing spaces on each line
		$result = \preg_replace('/ *$/m', '', $result);

		// Convert intentional indents with tabs into spaces
		$result = \str_replace("\t", '    ', $result);

		return \trim($result, "\n");
	}

	/**
	 * Unify newlines; in particular, \r\n becomes \n, and
	 * then \r becomes \n. This means that all newlines (Unix, Windows, Mac)
	 * all become \ns.
	 *
	 * @param text text with any number of \r, \r\n and \n combinations
	 * @return the fixed text
	 */
	private function fixNewlines( $text )
	{
		// replace \r\n to \n
		$text = \str_replace("\r\n", "\n", $text);
		// remove \rs
		$text = \str_replace("\r", "\n", $text);

		return $text;
	}

	// @codingStandardsIgnoreStart
	private function parseNode( DOMNode $node )
	{
		if( $node instanceof DOMText )
		{
			return \preg_replace('/\\s+/', ' ', $node->wholeText);
		}

		$name = \strtolower($node->nodeName);

		switch( $name )
		{
			case 'h1':
				return "\n"
					. \str_repeat('*', 79) . "\n"
					. \mb_strtoupper($this->parseChildNodes($node)) . "\n"
					. \str_repeat('*', 79)
					. ($this->isLastChildOfBlockLevelElement($node) ? '' : "\n\n");

			case 'h2':
				return "\n"
					. \mb_strtoupper($this->parseChildNodes($node)) . "\n"
					. \str_repeat('=', 79)
					. ($this->isLastChildOfBlockLevelElement($node) ? '' : "\n\n");

			case 'h3':
				return "\n"
					. \mb_strtoupper($this->parseChildNodes($node)) . "\n"
					. \str_repeat('-', 79)
					. ($this->isLastChildOfBlockLevelElement($node) ? '' : "\n\n");

			case 'h4':
				return \mb_strtoupper($this->parseChildNodes($node))
					. ($this->isLastChildOfBlockLevelElement($node) ? '' : "\n\n");

			case 'h5':
			case 'h6':
			case 'h7':
			case 'h8':
			case 'h9':
			case 'p':
			case 'div':
			case 'ol':
			case 'ul':
			case 'dl':
				return $this->parseChildNodes($node)
					. ($this->isLastChildOfBlockLevelElement($node) ? '' : "\n\n");

			case 'blockquote':
			case 'pre':
				return $this->indent($this->parseChildNodes($node))
					. ($this->isLastChildOfBlockLevelElement($node) ? '' : "\n\n");

			case 'li':
				return ' * ' . $this->parseChildNodes($node)
					. ($this->isLastChildOfBlockLevelElement($node) ? '' : "\n");

			case 'dd':
			case 'dt':
				return $this->parseChildNodes($node)
					. ($this->isLastChildOfBlockLevelElement($node) ? '' : "\n");

			case 'a':
				return $this->renderLink($node);

			case 'br':
				return "\n";

			case 'hr':
				return "\n"
					. \str_repeat('-', 79)
					. ($this->isLastChildOfBlockLevelElement($node) ? '' : "\n\n");

			case 'style':
			case 'head':
			case 'title':
			case 'meta':
			case 'link':
			case 'script':
				// ignore these tags
				return '';

			default:
				// print out contents of unknown tags
				return $this->parseChildNodes($node);
		}
	}
	// @codingStandardsIgnoreEnd

	private function parseChildNodes( DOMNode $node )
	{
		if( !$node->childNodes )
		{
			return '';
		}

		$result = '';
		for( $i = 0; $i < $node->childNodes->length; $i++ )
		{
			$childNode = $node->childNodes->item($i);
			$result .= $this->parseNode($childNode);
		}
		return $result;
	}

	private function renderLink( DOMElement $node )
	{
		$text = $this->parseChildNodes($node);
		$href = $node->getAttribute('href');
		if( !$text )
		{
			return $href;
		}
		if( !$href )
		{
			return $text;
		}

		// Remove "mailto" pseudo-protocol
		if( \mb_strpos($href, 'mailto:') === 0 )
		{
			$href = \mb_substr($href, 7);
		}

		// Text contained in href (e.g. URL or URL without "http://") -> no label
		if( \mb_strpos($href, $text) !== false )
		{
			return $href . ' ';
		}

		return $text . ' (' . $href . ')';
	}

	private function indent( $text )
	{
		$result = [];
		foreach( \explode("\n", $text) as $line )
		{
			$result[] = "\t" . $line;
		}
		return \implode("\n", $result);
	}

	private function isLastChildOfBlockLevelElement( DOMNode $node )
	{
		return !$this->getNextSiblingElement($node)
			&& $node->parentNode && $this->isBlockLevelElement($node->parentNode);
	}

	private function getNextSiblingElement( DOMNode $node )
	{
		$nextNode = $node->nextSibling;
		while( $nextNode )
		{
			if( $nextNode instanceof DOMElement )
			{
				return $nextNode;
			}
			$nextNode = $nextNode->nextSibling;
		}
		return null;
	}

	private function isBlockLevelElement( DOMNode $node )
	{
		return \in_array(
			\strtolower($node->nodeName),
			['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'h7', 'h8', 'h9', 'div', 'p', 'blockquote', 'pre', 'ul', 'ol', 'li', 'dl', 'dd', 'dt']
		);
	}
}
