<?php
namespace XAF\test;

class XpathBuilder
{
	/**
	 * @param string $tag
	 * @param array $texts
	 * @param string $rootXpath
	 * @return string
	 */
	static public function elementByTextsOfContainedElements( $tag, array $texts, $rootXpath = '//' )
	{
		$containingXpath = '';
		foreach( $texts as $text )
		{
			if( $containingXpath === '' )
			{
				$containingXpath = './/*[' . self::containsText($text) . ']';
			}
			else
			{
				$containingXpath .= ' and .//*[' . self::containsText($text) . ']';
			}
		}
		return $rootXpath . $tag . '[' . $containingXpath . ']';
	}

	/**
	 * @param string $legend
	 * @param string $rootXpath
	 * @return string
	 */
	static public function fieldsetByLegend( $legend, $rootXpath = '//' )
	{
		return $rootXpath . 'fieldset[.//legend[' . self::containsText($legend) . ']]';
	}

	/**
	 * @param string $optionName
	 * @param string $rootXpath
	 * @return string
	 */
	static public function selectorByOption( $optionName, $rootXpath = '//' )
	{
		$optionXpath = self::elementByText('option', $optionName);
		return $rootXpath . 'select[.' . $optionXpath . ']';
	}

	/**
	 * @param string $tag
	 * @param string $text
	 * @param string $rootXpath
	 * @return string
	 */
	static public function elementByText( $tag, $text, $rootXpath = '//' )
	{
		return $rootXpath . $tag . '[descendant-or-self::*[' . self::containsText($text) . ']]';
	}

	/**
	 * @param string $tag
	 * @param string $text
	 * @param string $attributeName
	 * @param string $rootXpath
	 * @return string
	 */
	static public function elementByTextHavingAttribute( $tag, $text, $attributeName, $rootXpath = '//' )
	{
		return $rootXpath . $tag . '[@' . $attributeName . ' and descendant-or-self::*[' . self::containsText($text) . ']]';
	}

	/**
	 * @param string $text
	 * @return string
	 */
	static private function containsText( $text )
	{
		return 'contains(., "' . $text . '")';
	}
}
