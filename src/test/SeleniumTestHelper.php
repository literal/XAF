<?php
namespace XAF\test;

use PHPUnit_Extensions_SeleniumTestCase as TestCase;

class SeleniumTestHelper
{
	/** @var TestCase */
	private $testCase;

	public function __construct( TestCase $testCase )
	{
		$this->testCase = $testCase;
	}

	/***** NAVIGATION / LOCATION *****/

	/**
	 * Will stop as soon as a page with the given title is reached, i.e. not do anything if already on that page
	 *
	 * @param string Title of the target page
	 * @param array $links array of text links leading to the expected page
	 */
	public function navigateToPageWithTitleViaLinks( $title, array $links )
	{
		if( !$this->doesCurrentTitleContain($title) )
		{
			$this->navigate($links);
			$this->assertTitleContains($title);
		}
	}

	/**
	 * @param array $links array of text links leading to the expected page
	 * @return bool;
	 */
	public function navigate( array $links )
	{
		$naviLinks = \array_reverse($links);
		$linksNotFound = [];
		foreach( $naviLinks as $link )
		{
			$xpath = $this->buildXpathForLinkOrButtonByLabel($link);
			if( $xpath != null )
			{
				$this->clickLinkOrButtonAndWait($link);
				if( !$linksNotFound || \call_user_func([$this, 'navigate'], \array_reverse($linksNotFound)) )
				{
					return true;
				}
			}
			else
			{
				$linksNotFound[] = $link;
			}
		}
		return false;
	}

	/**
	 * @param string $linkText
	 * @param string $rootXpath
	 */
	public function clickLinkOrButtonAndWait( $linkText, $rootXpath = '//' )
	{
		$xpath = $this->buildXpathForLinkOrButtonByLabel($linkText, $rootXpath);
		$this->testCase->clickAndWait('xpath=' . $xpath);
	}

	/**
	 * @param string $linkText
	 * @param string $rootXpath
	 */
	public function clickLinkOrButton( $linkText, $rootXpath = '//' )
	{
		$xpath = $this->buildXpathForLinkOrButtonByLabel($linkText, $rootXpath);
		$this->testCase->click('xpath=' . $xpath);
	}

	/**
	 * @param string $label
	 * @param string $rootXpath
	 */
	public function waitForLinkOrButtonByLabel( $label, $rootXpath = '//' )
	{
		$maxRetries = 100;
		while( !$this->buildXpathForLinkOrButtonByLabel($label, $rootXpath) && $maxRetries > 0 )
		{
			\usleep(250000); // 250 msec
			$maxRetries--;
		}

		if( $maxRetries < 1 )
		{
			$this->testCase->fail('Button or link "' . $label . '" not found. Giving up...');
		}
	}

	/**
	 * @param string $label
	 * @param string $rootXpath
	 * @return string|null
	 */
	public function buildXpathForLinkOrButtonByLabel( $label, $rootXpath = '//' )
	{
		// This could be done with an Xpath union ('|'), but selenium does not seem to support unions...
		$candidates = [
			$rootXpath . 'a[descendant-or-self::*[contains(., "' . $label . '")]]',
			$rootXpath . 'button[descendant-or-self::*[contains(., "' . $label . '")] and not(@disabled)]',
			$rootXpath . 'input[(@type="submit" or @type="button") and contains(@value, "' . $label . '") and not(@disabled)]'
		];

		foreach( $candidates as $candidate )
		{
			if( $this->doesXpathExist($candidate) )
			{
				return $candidate;
			}
		}

		return null;
	}

	/**
	 * @param string $desiredUrl
	 * @param array $params
	 */
	public function openIfNotCurrentKeepQueryParams( $desiredUrl, array $params = [] )
	{
		$url = $this->removeQueryStringFromUrl($desiredUrl);
		$queryParamsString = $this->mergeQueryParamsIfPresent($this->getLocation(), $desiredUrl);
		$newUrl = $url . ($queryParamsString !== '' ? '?' . $queryParamsString : '');
		$this->openIfNotCurrent($newUrl, $params);
	}

	/**
	 * @param string $url
	 * @param string $path
	 * @return string
	 */
	public function addPathToUrl( $url, $path )
	{
		$newUrl = $this->removeQueryStringFromUrl($url) . $path;
		$queryString = $this->mergeQueryParamsIfPresent($url, $newUrl);
		return $newUrl . ($queryString !== '' ? '?' . $queryString : '');
	}

	/**
	 * @param string $url1
	 * @param string $url2
	 * @return string
	 */
	private function mergeQueryParamsIfPresent( $url1, $url2 )
	{
		$mergedParamHashes = \array_merge($this->getQueryParamsFromUrl($url1), $this->getQueryParamsFromUrl($url2));
		return \http_build_query($mergedParamHashes, '&');
	}

	/**
	 * @param string $url
	 * @param array $params
	 */
	public function openIfNotCurrent( $url, array $params = [] )
	{
		if( $params != [] )
		{
			$currentQueryParams = $this->getQueryParamsFromUrl($url);
			$url = $this->removeQueryStringFromUrl($url) . '?' . \http_build_query(\array_merge($currentQueryParams, $params));
		}
		if( $this->differsFromCurrentLocation($url) )
		{
			$this->testCase->open($url);
			$this->testCase->waitForPageToLoad();
		}
	}

	/**
	 * @param string $url
	 * @return array
	 */
	private function getQueryParamsFromUrl( $url )
	{
		$queryString = \parse_url($url, \PHP_URL_QUERY);
		\parse_str($queryString, $result);
		return $result;
	}

	/**
	 * @return string
	 */
	public function getCurrentLocationWithoutQueryParams()
	{
		return $this->removeQueryStringFromUrl($this->getLocation());
	}

	/**
	 * @param string $url
	 * @return string
	 */
	private function removeQueryStringFromUrl( $url )
	{
		$queryStringStart = \strpos($url, '?');
		if( $queryStringStart !== false )
		{
			return \substr($url, 0, $queryStringStart);
		}
		return $url;
	}

	/**
	 * @param string $url
	 * @return bool
	 */
	private function differsFromCurrentLocation( $url )
	{
		return $this->getLocation() != $url;
	}

	/**
	 * @return string|null
	 */
	private function getLocation()
	{
		try
		{
			return $this->testCase->getLocation();
		}
		catch( \Exception $e )
		{
			return null;
		}
	}

	/**
	 * @param string $columnTitle
	 * @param string $tableRootXpath
	 * @return int|null
	 */
	public function getTableColumnIndexByColumnTitle( $columnTitle, $tableRootXpath = '//table//' )
	{
		$colCount = $this->testCase->getXpathCount($tableRootXpath . 'tr/th');
		for( $colNumber = 1; $colNumber <= $colCount; $colNumber++ )
		{
			$fieldName = $this->testCase->getText('xpath=' . $tableRootXpath . 'tr/th[' . $colNumber . ']');
			if( $columnTitle == \str_replace("\n", '', $fieldName) )
			{
				return $colNumber;
			}
		}
		return null;
	}

	/**
	 * @param string $tableXpath
	 * @return array Nested array - rows/columns
	 */
	public function getTableContents( $tableXpath = '//table' )
	{
		$result = [];
		$rowCount = $this->testCase->getXpathCount($tableXpath . '//tr[./td]');
		for( $rowNumber = 1; $rowNumber <= $rowCount; $rowNumber++ )
		{
			$resultRow = [];
			$rowXpath = $tableXpath . '//tr[./td][' . $rowNumber . ']';
			$colCount = $this->testCase->getXpathCount($rowXpath . '/td');
			for( $colNumber = 1; $colNumber <= $colCount; $colNumber++ )
			{
				$resultRow[] = \trim($this->testCase->getText('xpath=' . $rowXpath . '/td[' . $colNumber . ']'));
			}
			$result[] = $resultRow;
		}
		return $result;
	}

	/***** ASSERTS *****/

	/**
	 * @param string $text
	 */
	public function assertTitleContains( $text )
	{
		$this->testCase->assertElementContainsText('xpath=//title', $text);
	}

	/**
	 * @param string $text
	 */
	public function assertTitleNotContains( $text )
	{
		$this->testCase->assertElementNotContainsText('xpath=//title', $text);
	}

	/**
	 * @param string $value
	 * @param string $fieldLabel
	 * @param string $rootXpath
	 */
	public function assertInputFieldContainsValueByLabel( $value, $fieldLabel, $rootXpath = '//' )
	{
		$inputId = $this->getInputFieldIdByLabel($fieldLabel, $rootXpath);
		$this->testCase->assertElementValueContains('id=' . $inputId, $value);
	}

	/**
	 * @param string $value
	 * @param string $fieldsetLegend
	 * @param string $fieldLabel
	 * @param string $rootXpath
	 */
	public function assertInputFieldContainsValueByLegendAndLabel( $value, $fieldsetLegend, $fieldLabel, $rootXpath = '//' )
	{
		$inputId = $this->getInputFieldIdByLegendAndLabel($fieldsetLegend, $fieldLabel, $rootXpath);
		$this->testCase->assertElementValueContains('id=' . $inputId, $value);
	}

	/**
	 * @param string $optionLabel
	 * @param string $fieldLabel
	 * @param string $rootXpath
	 */
	public function assertSelectedOptionLabelIsByLabel( $optionLabel, $fieldLabel, $rootXpath = '//' )
	{
		$inputId = $this->getInputFieldIdByLabel($fieldLabel, $rootXpath);
		$this->testCase->assertSelectedLabel('id=' . $inputId, $optionLabel);
	}

	/**
	 * @param string $optionLabel
	 * @param string $fieldsetLegend
	 * @param string $fieldLabel
	 * @param string $rootXpath
	 */
	public function assertSelectedOptionLabelIsByLegendAndLabel( $optionLabel, $fieldsetLegend, $fieldLabel, $rootXpath = '//' )
	{
		$inputId = $this->getInputFieldIdByLegendAndLabel($fieldsetLegend, $fieldLabel, $rootXpath);
		$this->testCase->assertSelectedLabel('id=' . $inputId, $optionLabel);
	}

	/**
	 * @param bool $checked
	 * @param string $fieldLabel
	 * @param string $rootXpath
	 */
	public function assertCheckboxStateByLabel( $checked, $fieldLabel, $rootXpath = '//' )
	{
		$checkboxId = $this->getCheckboxIdByLabel($fieldLabel, $rootXpath);
		$this->assertCheckboxState($checked, 'id=' . $checkboxId);
	}

	/**
	 * @param bool $checked
	 * @param string $fieldsetLegend
	 * @param string $fieldLabel
	 * @param string $rootXpath
	 */
	public function assertCheckboxStateByLegendAndLabel( $checked, $fieldsetLegend, $fieldLabel, $rootXpath = '//' )
	{
		$checkboxId = $this->getCheckboxIdByLegendAndLabel($fieldsetLegend, $fieldLabel, $rootXpath);
		$this->assertCheckboxState($checked, 'id=' . $checkboxId);
	}

	/**
	 * @param bool $checked
	 * @param string $locator
	 */
	private function assertCheckboxState( $checked, $locator )
	{
		if( $checked )
		{
			$this->testCase->assertChecked($locator);
		}
		else
		{
			$this->testCase->assertNotChecked($locator);
		}
	}

	/***** ELEMENT ACCESS *****/

	/**
	 * @param string $value
	 * @param string $fieldLabel
	 * @param string $rootXpath
	 */
	public function typeInputFieldByLabel( $value, $fieldLabel, $rootXpath = '//' )
	{
		$inputId = $this->getInputFieldIdByLabel($fieldLabel, $rootXpath);
		$this->testCase->type('id=' . $inputId, $value);
	}

	/**
	 * @param string $fieldLabel
	 * @param string $rootXpath
	 */
	public function focusInputFieldByLabel( $fieldLabel, $rootXpath = '//' )
	{
		$inputId = $this->getInputFieldIdByLabel($fieldLabel, $rootXpath);
		$this->testCase->click('id=' . $inputId);
	}

	/**
	 * @param string $value
	 * @param string $fieldsetLegend
	 * @param string $fieldLabel
	 * @param string $rootXpath
	 */
	public function typeInputFieldByLegendAndLabel( $value, $fieldsetLegend, $fieldLabel, $rootXpath = '//' )
	{
		$inputId = $this->getInputFieldIdByLegendAndLabel($fieldsetLegend, $fieldLabel, $rootXpath);
		$this->testCase->type('id=' . $inputId, $value);
	}

	/**
	 * @param string $value
	 * @param string $fieldLabel
	 * @param string $rootXpath
	 */
	public function chooseSelectOptionByLabel( $value, $fieldLabel, $rootXpath = '//' )
	{
		$inputId = $this->getInputFieldIdByLabel($fieldLabel, $rootXpath);
		$this->testCase->select('id=' . $inputId, $value);
	}

	/**
	 * @param string $value
	 * @param string $fieldsetLegend
	 * @param string $fieldLabel
	 * @param string $rootXpath
	 */
	public function chooseSelectOptionByLegendAndLabel( $value, $fieldsetLegend, $fieldLabel, $rootXpath = '//' )
	{
		$inputId = $this->getInputFieldIdByLegendAndLabel($fieldsetLegend, $fieldLabel, $rootXpath);
		$this->testCase->select('id=' . $inputId, $value);
	}

	/**
	 * @param bool $state
	 * @param string $fieldLabel
	 * @param string $rootXpath
	 */
	public function setCheckboxStateByLabel( $state, $fieldLabel, $rootXpath = '//' )
	{
		$id = $this->getCheckboxIdByLabel($fieldLabel, $rootXpath);
		$this->setCheckboxStateById($state, $id);
	}

	/**
	 * @param bool $state
	 * @param string $fieldsetLegend
	 * @param string $fieldLabel
	 * @param string $rootXpath
	 */
	public function setCheckboxStateByLegendAndLabel( $state, $fieldsetLegend, $fieldLabel, $rootXpath = '//' )
	{
		$id = $this->getCheckboxIdByLegendAndLabel($fieldsetLegend, $fieldLabel, $rootXpath);
		$this->setCheckboxStateById($state, $id);
	}

	/**
	 * @param bool $state
	 * @param string $id
	 */
	private function setCheckboxStateById( $state, $id )
	{
		if( $state )
		{
			$this->testCase->check('id=' . $id);
		}
		else
		{
			$this->testCase->uncheck('id=' . $id);
		}
	}

	/**
	 * @param string $text
	 * @param string $rootXpath
	 * @return string
	 */
	public function buildXpathForCheckboxInTableRowContainingText( $text, $rootXpath = '//' )
	{
		$rowXpath = XpathBuilder::elementByText('tr', $text, $rootXpath);
		return $rowXpath . '//input[@type="checkbox"]';
	}

	/**
	 * @param string $labelText
	 * @param string $rootXpath
	 * @return string
	 */
	public function getInputFieldIdByLabel( $labelText, $rootXpath = '//' )
	{
		$labelXpath = XpathBuilder::elementByText('label', $labelText, $rootXpath);
		return $this->getAttributeForElement('for', $labelXpath);
	}

	/**
	 * @param string $legend
	 * @param string $labelText
	 * @param string $rootXpath
	 * @return string
	 */
	public function getInputFieldIdByLegendAndLabel( $legend, $labelText, $rootXpath = '//' )
	{
		$labelXpath = $this->buildLabelXpathFromLegendAndLabel($legend, $labelText, $rootXpath);
		return $this->getAttributeForElement('for', $labelXpath);
	}

	/**
	 * @param string $labelText
	 * @param string $rootXpath
	 * @return string
	 */
	public function getCheckboxIdByLabel( $labelText, $rootXpath = '//' )
	{
		$labelXpathWithForAttribute = XpathBuilder::elementByTextHavingAttribute('label', $labelText, 'for', $rootXpath);
		$labelXpath = XpathBuilder::elementByText('label', $labelText, $rootXpath);
		return $this->doesXpathExist($labelXpathWithForAttribute)
			// Input element ID explicitly specified in label's "for" attribute
			?  $this->getAttributeForElement('for', $labelXpath)
			// Input element a child of the label element
			: $this->getAttributeForElement('id', $labelXpath . '/input');
	}

	/**
	 * @param string $legend
	 * @param string $labelText
	 * @param string $rootXpath
	 * @return string
	 */
	public function getCheckboxIdByLegendAndLabel( $legend, $labelText, $rootXpath = '//' )
	{
		$labelXpath = $this->buildLabelXpathFromLegendAndLabel($legend, $labelText, $rootXpath);
		return $this->getAttributeForElement('id', $labelXpath . '/input');
	}

	/**
	 * @param string $legend
	 * @param string $labelText
	 * @param string $rootXpath
	 * @return string
	 */
	public function buildLabelXpathFromLegendAndLabel( $legend, $labelText, $rootXpath = '//' )
	{
		$fieldsetRootXpath = XpathBuilder::fieldsetByLegend($legend, $rootXpath);
		return XpathBuilder::elementByText('label', $labelText, $fieldsetRootXpath . '//');
	}

	/**
	 * @param string $text
	 * @param string $linkText
	 * @param string $rootXpath
	 * @return string
	 */
	public function buildXpathForLinkInTableRowContainingText( $text, $linkText, $rootXpath = '//' )
	{
		$rowXpath = XpathBuilder::elementByText('tr', $text, $rootXpath);
		return $this->buildXpathForLinkOrButtonByLabel($linkText, $rowXpath . '//');
	}

	/**
	 * @param string $tableXpath
	 * @return int
	 */
	public function getNumberOfRowsInTable( $tableXpath )
	{
		return $this->testCase->getXpathCount($tableXpath . '//tr');
	}

	/**
	 * @return string
	 */
	public function getCurrentTitle()
	{
		return $this->testCase->isElementPresent('xpath=//title') ? $this->testCase->getText('xpath=//title') : '';
	}

	/**
	 * @param string $substring
	 * @return bool
	 */
	public function doesCurrentTitleContain( $substring )
	{
		return \strpos($this->getCurrentTitle(), $substring) !== false;
	}

	/**
	 * @param string $attribute
	 * @param string $elementXpath
	 * @return string
	 */
	public function getAttributeForElement( $attribute, $elementXpath )
	{
		if( !$this->doesXpathExist($elementXpath) )
		{
			$this->testCase->fail('element not present ' . $elementXpath);
		}
		return $this->testCase->getAttribute($elementXpath . '@' . $attribute);
	}

	/**
	 * @param string $xpath
	 * @return bool
	 */
	public function doesXpathExist( $xpath )
	{
		return $xpath && $this->testCase->isElementPresent($xpath);
	}
}
