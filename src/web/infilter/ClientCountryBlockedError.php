<?php
namespace XAF\web\infilter;

use XAF\exception\UserlandError;

class ClientCountryBlockedError extends UserlandError
{
	/** @var string */
	protected $clientCountryCode;

	/** @var array */
	protected $allowedCountryCodes;

	/**
	 * @param string $clientCountryCode
	 * @param array $allowedCountryCodes
	 */
	public function __construct( $clientCountryCode, array $allowedCountryCodes )
	{
		parent::__construct([
			'clientCountryCode' => $clientCountryCode,
			'allowedCountryCodes' => $allowedCountryCodes
		]);
	}
}
