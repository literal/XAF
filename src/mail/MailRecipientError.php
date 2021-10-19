<?php
namespace XAF\mail;

use XAF\exception\ValueRelatedError;

class MailRecipientError extends ValueRelatedError
{
	/** @var array */
	private $failedAddresses;

	public function __construct(array $failedAddresses, $smtpLog = null)
	{
		$this->failedAddresses = $failedAddresses;
		parent::__construct('Failed to send e-mail to one or more recipients', $failedAddresses, $smtpLog);
	}

	public function getFailedAddresses(): array
	{
		return $this->failedAddresses;
	}
}
