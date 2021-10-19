<?php
namespace XAF\mail;

use XAF\exception\ValueRelatedError;

class MailSendingError extends ValueRelatedError
{
	public function __construct($message, $smtpLog = null)
	{
		parent::__construct('Failed to send e-mail', $message, $smtpLog);
	}
}
