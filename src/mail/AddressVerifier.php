<?php
namespace XAF\mail;

interface AddressVerifier
{
	/**
	 * @param string $address
	 * @return bool
	 */
	public function isValid( $address );
}
