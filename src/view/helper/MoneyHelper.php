<?php
namespace XAF\view\helper;

use XAF\type\Money;

/**
 * What the EU has to say about money format (as far as the euro is concerned):
 *    http://publications.europa.eu/code/en/en-370300.htm
 */
class MoneyHelper
{
	/** @var NumberHelper */
	protected $numberHelper;

	/** @var array */
	static protected $currencyMap = [
		'EUR' => ['symbol' => '€'],
		'GBP' => ['symbol' => '£'],
		'USD' => ['symbol' => '$'],
		'JPY' => ['symbol' => '¥']
	];

	static protected $currencyBeforeAmount = true;
	static protected $currencySymbolSeparator = '';
	static protected $currencyCodeSeparator = ' ';
	static protected $separateThousands = true;

	public function __construct( NumberHelper $numberHelper )
	{
		$this->numberHelper = $numberHelper;
	}

	/**
	 * @param Money|array|mixed $value
	 * @param bool $useSymbol Use local currency symbol if available, e.g. '$' instead of 'USD'
	 * @return string
	 */
	public function formatMoney( $value, $useSymbol = true )
	{
		list($amount, $currency) = $this->splitMoney($value);
		$amountString = $this->formatAmount($amount);
		$currencyString = $useSymbol ? $this->formatCurrency($currency) : $currency;
		$separator = $useSymbol && isset(static::$currencyMap[$currency])
			? static::$currencySymbolSeparator
			: static::$currencyCodeSeparator;
		return static::$currencyBeforeAmount
			? $currencyString . $separator . $amountString
			: $amountString . $separator . $currencyString;
	}

	/**
	 * @param mixed $amount
	 * @param string $default
	 * @return string
	 */
	public function formatAmount( $amount, $default = '' )
	{
		return \strval(\intval($amount, 10)) == $amount
			? $this->numberHelper->formatNumber($amount / 100, 2, static::$separateThousands)
			: $default;
	}

	/**
	 * @param string $code ISO 4217 currency code (e.g. 'EUR' or 'USD')
	 * @return string
	 */
	public function formatCurrency( $code )
	{
		return isset(self::$currencyMap[$code]) ? self::$currencyMap[$code]['symbol'] : $code;
	}

	/**
	 * @param Money|array|mixed $value
	 * @param string $default
	 * @return string
	 */
	public function extractAmountAsNumber( $value, $default = '0.00' )
	{
		list($amount) = $this->splitMoney($value);
		return \strval(\intval($amount, 10)) == $amount
			? \number_format($amount / 100, 2, '.', '')
			: $default;
	}

	/**
	 * @param Money|array|mixed $value
	 * @return array [<amount>, <currency code>]
	 */
	protected function splitMoney( $value )
	{
		if( $value instanceof Money )
		{
			return [$value->amount, $value->currency];
		}
		if( \is_array($value) && isset($value['amount'], $value['currency']) )
		{
			return [$value['amount'], $value['currency']];
		}
		return [null, null];
	}

}
