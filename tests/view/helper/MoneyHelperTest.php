<?php
namespace XAF\view\helper;

use PHPUnit\Framework\TestCase;
use XAF\type\Money;

/**
 * @covers \XAF\view\helper\MoneyHelper
 */
class MoneyHelperTest extends TestCase
{
	/** @var MoneyHelper */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new MoneyHelper(new NumberHelper());
	}

	public function testFormatMoneyAcceptsMoneyObject()
	{
		$result = $this->object->formatMoney(new Money(123, 'EUR'));

		$this->assertEquals('€1.23', $result);
	}

	public function testFormatMoneyAcceptsArray()
	{
		$result = $this->object->formatMoney(['amount' => 123, 'currency' => 'EUR']);

		$this->assertEquals('€1.23', $result);
	}

	public function testFormatMoneyPutsASpaceAfterCurrencyCode()
	{
		$result = $this->object->formatMoney(['amount' => 123, 'currency' => 'EUR'], false);

		$this->assertEquals('EUR 1.23', $result);
	}

	public function testFormatMoneyUsesThousandsSeparator()
	{
		$result = $this->object->formatMoney(['amount' => 1234567, 'currency' => 'USD']);

		$this->assertEquals('$12,345.67', $result);
	}

	public function testMoneyAmountIsDividedBy100()
	{
		$result = $this->object->formatAmount(123);

		$this->assertEquals('1.23', $result);
	}

	public function testFormatCurrency()
	{
		$result = $this->object->formatCurrency('EUR');

		$this->assertEquals('€', $result);
	}

	public function testCurrencyCodeIsUsedIfNoSymbolIsAvailable()
	{
		$result = $this->object->formatCurrency('DKK');

		$this->assertEquals('DKK', $result);
	}

	public function testExtractAmountAsNumberAcceptsMoneyObject()
	{
		$result = $this->object->extractAmountAsNumber(new Money(389950, 'EUR'));

		$this->assertEquals('3899.50', $result);
	}

	public function testExtractAmountAsNumberAcceptsArray()
	{
		$result = $this->object->extractAmountAsNumber(['amount' => 123, 'currency' => 'JPY']);

		$this->assertEquals('1.23', $result);
	}

	public function testExtractAmountAsNumberReturnsZeroWhenCalledWithUnsupportedArgument()
	{
		$result = $this->object->extractAmountAsNumber(789);

		$this->assertEquals('0.00', $result);
	}

	public function testExtractAmountAsNumberUsesSpecifiedDefaultValueWhenCalledWIthoutValidMoneyValue()
	{
		$result = $this->object->extractAmountAsNumber(true, '---');

		$this->assertEquals('---', $result);
	}
}
