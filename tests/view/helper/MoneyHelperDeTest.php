<?php
namespace XAF\view\helper;

use PHPUnit\Framework\TestCase;
use XAF\type\Money;

/**
 * Only differences to MoneyHelper are tested
 *
 * @covers \XAF\view\helper\MoneyHelper
 */
class MoneyHelperDeTest extends TestCase
{
	/** @var MoneyHelper */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new MoneyHelperDe(new NumberHelperDe());
	}

	public function testFormatMoneyPutsSpaceBeforeCurrencySymbol()
	{
		$result = $this->object->formatMoney(['amount' => 123, 'currency' => 'EUR']);

		$this->assertEquals('1,23 €', $result);
	}

	public function testFormatMoneyPutsSpaceBeforeCurrencyCode()
	{
		$result = $this->object->formatMoney(['amount' => 123, 'currency' => 'EUR'], false);

		$this->assertEquals('1,23 EUR', $result);
	}

	public function testExtractAmountAsNumberDoesNotUseLocalizedDecimalSeparator()
	{
		$result = $this->object->extractAmountAsNumber(new Money(444, 'EUR'));

		$this->assertEquals('4.44', $result);
	}
}
