<?php
namespace XAF\validate;

use XAF\type\Money;

/**
 * @errorKey missingCurrency
 * @errorKey invalidCurrency(currency)
 */
class MoneyValidator extends NumberValidator
{
	public function validate( $value, $defaultCurrency = null, $minAmount = null )
	{
		if( !($value instanceof Money) )
		{
			$amount = \is_array($value) ? ($value['amount'] ?? null) : $value;
			$amountResult = parent::validate($amount, $minAmount);
			if( $amountResult->errorKey !== null )
			{
				return $amountResult;
			}

			$currency = \is_array($value) && isset($value['currency']) && \trim($value['currency']) !== ''
				? \mb_strtoupper(\trim($value['currency']))
				: $defaultCurrency;

			if( $currency === null || $currency === '' )
			{
				return ValidationResult::createError('missingCurrency');
			}

			if( !\preg_match('/^[A-Z]{3}$/', $currency) )
			{
				return ValidationResult::createError('invalidCurrency', ['currency' => $currency]);
			}

			$value = new Money(\intval(\round($amountResult->value * 100), 10), $currency);
		}

		return ValidationResult::createValid($value);
	}
}
