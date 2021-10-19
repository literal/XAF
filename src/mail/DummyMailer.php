<?php
namespace XAF\mail;

use XAF\file\FileHelper;

/**
 * Dummy Mailer used for testing purposes. Mails are not sent but written to an mbox-style text file.
 */
class DummyMailer implements Mailer
{
	const START_OF_MAIL_MARKER = '<<< START OF DUMPED MAIL >>>';

	/** @var string */
	private $dumpFile;

	/** @var int */
	private $fileMode;

	/** @var FileHelper */
	protected $fileHelper;

	public function __construct( FileHelper $fileHelper, $dumpFile, $fileMode = 0666 )
	{
		$this->dumpFile = $dumpFile;
		$this->fileMode = $fileMode;
		$this->fileHelper = $fileHelper;
	}

	/**
	 * @param Mail $mail
	 */
	public function send( Mail $mail )
	{
		$now = \time();
		$dateWithTimezone = \date('j F Y H:i:s O', $now);

		$mailDump = "\n" . self::START_OF_MAIL_MARKER . "\n" .
			'Date: ' . $dateWithTimezone . "\n" .
			'From: ' . $mail->senderName . ' <' . $mail->senderAddress . '>' . "\n";

		foreach( $mail->recipients as $recipient )
		{
			$mailDump .= $this->buildRecipientHeader($recipient) . "\n";
		}

		$mailDump .= 'Subject: ' . $mail->subject . "\n" .
			"\n" .
			$this->escapeMailText($mail->textBody) . "\n" .
			"\n" .
			$this->escapeMailText($mail->htmlBody) . "\n" .
			"\n\n";

		foreach( $mail->attachedFiles as $fileName => $fileContents )
		{
			$mailDump .= '[ATTACHED FILE ' . $fileName . ': ' . \strlen($fileContents) . ' bytes]' . "\n";
		}
		if( $mail->attachedFiles )
		{
			echo "\n\n";
		}

		$this->fileHelper->appendToFileFromString($this->dumpFile, $mailDump);
		$this->fileHelper->setPermissions($this->dumpFile, $this->fileMode);
	}

	/**
	 * @param MailRecipient $recipient
	 * @return string
	 */
	private function buildRecipientHeader( MailRecipient $recipient )
	{
		$fieldNamesByRecipientType = [
			MailRecipient::TYPE_TO => 'To',
			MailRecipient::TYPE_CC => 'CC',
			MailRecipient::TYPE_BCC => 'BCC',
		];
		return isset($fieldNamesByRecipientType[$recipient->type])
			? $fieldNamesByRecipientType[$recipient->type] . ': ' . $recipient->name . ' <' . $recipient->address . '>'
			: '';
	}

	/**
	 * Every Mail Entry starts with "From ", thats why it has to be escaped. This is a common escaping method
	 * in mbox-type files.
	 *
	 * @param string $text
	 * @return string
	 */
	private function escapeMailText( $text )
	{
		return \preg_replace('/^From /m', '<From ', $text);
	}
}
