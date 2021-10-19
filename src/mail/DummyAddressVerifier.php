<?php
namespace XAF\mail;

class DummyAddressVerifier implements AddressVerifier
{
	/**
	 * @param string $address
	 * @return bool
	 */
	public function isValid( $address )
	{
		return \filter_var($address, \FILTER_VALIDATE_EMAIL);
	}
}
