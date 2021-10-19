<?php
namespace XAF\mail;

/**
 * Public data structure for an eMail to be sent
 */
class Mail
{
	/** @var string */
	public $subject;

	/** @var string */
	public $senderName;

	/** @var string */
	public $senderAddress;

	/** @var MailRecipient[] */
	public $recipients = [];

	/** @var string */
	public $textBody;

	/** @var string|null */
	public $htmlBody;

	/** @var array {<string fileName>: <string binaryFileContents>, ...} */
	public $attachedFiles = [];
}
