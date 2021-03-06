<?php

namespace XAF\validate;

/**
 * Unlike the normal empty validator this also accepts a money struct with an empty amount field.
 */
class EmptyMoneyValidator extends EmptyValidator
{
	public function validate( $value )
	{
		return parent::validate(\is_array($value) ? ($value['amount'] ?? null) : $value);
	}
}
