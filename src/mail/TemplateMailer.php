<?php
namespace XAF\mail;

use XAF\view\TemplateRenderer;

/**
 * Send email built from a template
 *
 * The template is expected to have the following named blocks:
 * - subject
 * - senderName (optional)
 * - senderAddress
 * - textBody
 * - htmlBody (optional, escaping must be handled in template!)
 */
class TemplateMailer
{
	/** @var TemplateRenderer */
	private $renderer;

	/** @var Mailer */
	private $mailer;

	public function __construct( TemplateRenderer $renderer, Mailer $mailer )
	{
		$this->renderer = $renderer;
		$this->mailer = $mailer;
	}

	/**
	 * @param string $templateName
	 * @param array $templateContext
	 * @param MailRecipient[] $recipients
	 */
	public function sendMail( $templateName, array $templateContext, array $recipients )
	{
		$mail = new Mail;
		$mail->subject = $this->renderTemplateBlock($templateName, 'subject', $templateContext);
		$mail->senderAddress = $this->renderTemplateBlock($templateName, 'senderAddress', $templateContext);
		$mail->senderName = $this->renderTemplateBlock($templateName, 'senderName', $templateContext);
		$mail->recipients = $recipients;
		$mail->textBody = $this->renderTemplateBlock($templateName, 'textBody', $templateContext);

		$htmlBody = $this->renderTemplateBlock($templateName, 'htmlBody', $templateContext);
		$mail->htmlBody = $htmlBody !== '' ? $htmlBody : null;

		$this->mailer->send($mail);
	}

	/**
	 * @param string $templateName
	 * @param string $blockName
	 * @param array $templateContext
	 * @return string
	 */
	private function renderTemplateBlock( $templateName, $blockName, array $templateContext )
	{
		return \trim($this->renderer->renderNamedBlock($templateName, $blockName, $templateContext));
	}
}

