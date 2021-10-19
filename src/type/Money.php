<?php
namespace XAF\type;

class Money
{
	/** @var int in hundredths of the main currency unit */
	public $amount;

	/** @var string 3-letter ISO currency code */
	public $currency;

	public function __construct( $amount = null, $currency = null )
	{
		$this->amount = $amount;
		$this->currency = $currency;
	}

	public function __toString()
	{
		return $this->currency . (isset($this->amount) ? \number_format($this->amount / 100, 2) : '?');
	}
}
