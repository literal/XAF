<?php
namespace XAF\mail;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\mail\DummyMailer
 */
class DummyMailerTest extends TestCase
{
	const FULL_ACCESS_FILE_MODE = 0666;

	/**
	 * @var DummyMailer
	 */
	protected $object;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $fileHelperMock;

	/** @var string */
	private $dumpfile;

	protected function setUp(): void
	{
		$this->fileHelperMock = $this->getMockBuilder(\XAF\file\FileHelper::class)->getMock();
		$this->dumpfile = WORK_PATH . '/mail.dump';
		$this->object = new DummyMailer($this->fileHelperMock, $this->dumpfile, self::FULL_ACCESS_FILE_MODE);
	}

	public function testSendEscapesMailText()
	{
		$mail = $this->getSampleMail();
		$mail->textBody = "\n" . 'Hello Foo,' . "\n" . 'From Bar with love!';
		$expectedText = "\n" . 'Hello Foo,' . "\n" . '<From Bar with love!';

		$this->fileHelperMock
			->expects($this->once())
			->method('appendToFileFromString')
			->with($this->equalTo($this->dumpfile), $this->stringContains($expectedText));

		$this->object->send($mail);
	}

	public function testMailDumpFileIsCreatedWithFullAccessPermissions()
	{
		$this->fileHelperMock
			->expects($this->once())
			->method('setPermissions')
			->with($this->equalTo($this->dumpfile), self::FULL_ACCESS_FILE_MODE);

		$this->object->send($this->getSampleMail());
	}

	/**
	 * @return Mail
	 */
	private function getSampleMail()
	{
		$mail = new Mail;
		$mail->htmlBody = '<h1>Body</h1><p>Foo Bar!</p>';
		$mail->recipients[] = new MailRecipient('recipient@address.mail', 'Recipient Name');
		$mail->senderAddress = 'sender@address.mail';
		$mail->senderName = 'Sender Name';
		$mail->subject = 'subject';
		$mail->textBody = 'Body, ' . "\n" . 'Foo Bar!';
		return $mail;
	}

}
