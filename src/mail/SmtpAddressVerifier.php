<?php
namespace XAF\mail;

class SmtpAddressVerifier implements AddressVerifier
{
	private $connectionTimeoutSec = 5;

	/**
	 * @param string $address
	 * @return bool
	 */
	public function isValid( $address )
	{
		$address = $this->convertIdnInAddressToPunycode($address);
		return \filter_var($address, \FILTER_VALIDATE_EMAIL) && $this->doesReachableSmtpServerExistForAddress($address);
	}

	/**
	 * @param string $address
	 * @return string
	 */
	private function convertIdnInAddressToPunycode( $address )
	{
		list($localPart, $domain) = $this->splitAddress($address);
		return $localPart . ($domain !== '' ? '@' . \idn_to_ascii($domain) : '');
	}

	/**
	 * @param string $address
	 * @return array [<local part>, <domain>]
	 */
	private function splitAddress( $address )
	{
		$lastAtPos = \strrpos($address, '@');
		return $lastAtPos !== false
			? [\substr($address, 0, $lastAtPos), \substr($address, $lastAtPos + 1)]
			: [$address, ''];
	}

	/**
	 * @param string $address
	 * @return bool
	 */
	private function doesReachableSmtpServerExistForAddress( $address )
	{
		list($localPart, $domain) = $this->splitAddress($address);
		// Appended dot prevents resolution as local domain
		$domain .= '.';
		foreach( $this->getMxHostsForDomain($domain) as $host )
		{
			if( $this->isReachableSmtpServer($host) )
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $domain
	 * @return bool
	 */
	private function getMxHostsForDomain( $domain )
	{
		$hosts = [];
		if( \dns_get_mx($domain, $hosts, $weights) )
		{
			if( $hosts && $weights )
			{
				\array_multisort($weights, \SORT_DESC, \SORT_NUMERIC, $hosts);
			}
		}

		// According to RFC 2821 the domain name itself is to be used as fallback MX host when no DNS MX record exists.
		// But we only add it if there is any DNS information available at all (or else we will get a warning
		// when trying to connect)
		if( \dns_check_record($domain, 'ANY') )
		{
			$hosts[] = $domain;
		}

		return $hosts;
	}

	/**
	 * @param string $host
	 * @return bool
	 */
	private function isReachableSmtpServer( $host )
	{
		$handle = @\fsockopen($host, 25, $errno, $errstr, $this->connectionTimeoutSec);
		if( !$handle )
		{
			return false;
		}

		\fclose($handle);
		return true;
	}
}
