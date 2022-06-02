<?php
namespace XAF\mail;

use Swift_Mailer;
use Swift_Plugins_Logger;
use XAF\helper\MimeTypeResolver;
use Swift_Message;
use Swift_Attachment;
use Swift_RfcComplianceException;
use Swift_TransportException;

class SmtpMailer implements Mailer
{
	/** @var Swift_Mailer */
	protected $agent;

	/** @var MimeTypeResolver */
	private $mimeTypeResolver;

	/** @var Swift_Plugins_Logger|null */
	protected $logger;

	/**
	 * @param Swift_Mailer $agent
	 * @param MimeTypeResolver $mimeTypeResolver
	 * @param Swift_Plugins_Logger|null $logger Optional logger instance which will be tapped in case of a transport
	 *     error and must already be registered with the Swift_Mailer instance.
	 */
	public function __construct( Swift_Mailer $agent, MimeTypeResolver $mimeTypeResolver,
		Swift_Plugins_Logger $logger = null )
	{
		$this->agent = $agent;
		$this->logger = $logger;
		$this->mimeTypeResolver = $mimeTypeResolver;
	}

	/**
	 * @param Mail $mail
	 * @return bool
	 */
	public function send( Mail $mail )
	{
		$this->resetLogger();

		$message = new Swift_Message($mail->subject, $mail->textBody, 'text/plain', 'UTF-8');

		if( $mail->htmlBody !== null )
		{
			$message->addPart($mail->htmlBody, 'text/html', 'UTF-8');
		}

		foreach( $mail->attachedFiles as $fileName => $fileContents )
		{
			$message->attach(
				new Swift_Attachment(
					$fileContents,
					$fileName,
					$this->mimeTypeResolver->getMimeTypeFromFileName($fileName)
				)
			);
		}

		$message->setFrom($mail->senderAddress, $mail->senderName);
		$this->setReceipientsOnMessage($mail->recipients, $message);

		try
		{
			$this->agent->send($message, $failedRecipients);
		}
		catch( Swift_TransportException $e )
		{
			throw new MailSendingError($e->getMessage(), $this->getSmtpLog());
		}

		if( $failedRecipients )
		{
			throw new MailRecipientError($failedRecipients, $this->getSmtpLog());
		}
	}

	private function resetLogger()
	{
		if( $this->logger )
		{
			$this->logger->clear();
		}
	}

	/**
	 * @param MailRecipient[] $recipients
	 * @param type $message
	 */
	private function setReceipientsOnMessage(array $recipients, Swift_Message $message)
	{
		// Convert recipients into the crazy and hardly documented SwiftMailer format:
		// Array/hash mix of both {<address>: <name>, ...} and [<address>, ...]
		$recipientsByType = [];
		foreach( $recipients as $recipient )
		{
			if( $recipient->name !== null )
			{
				$recipientsByType[$recipient->type][$recipient->address] = $recipient->name;
			}
			else
			{
				$recipientsByType[$recipient->type][] = $recipient->address;
			}
		}

		try
		{
			if( isset($recipientsByType[MailRecipient::TYPE_TO]) )
			{
				$message->setTo($recipientsByType[MailRecipient::TYPE_TO]);
			}
			if( isset($recipientsByType[MailRecipient::TYPE_CC]) )
			{
				$message->setCc($recipientsByType[MailRecipient::TYPE_CC]);
			}
			if( isset($recipientsByType[MailRecipient::TYPE_BCC]) )
			{
				$message->setBcc($recipientsByType[MailRecipient::TYPE_BCC]);
			}
		}
		catch( Swift_RfcComplianceException $e )
		{
			throw new MailRecipientError(array_column($recipients, 'address'));
		}
	}

	/**
	 * @return string|null
	 */
	private function getSmtpLog()
	{
		return $this->logger ? $this->logger->dump() : null;
	}
}