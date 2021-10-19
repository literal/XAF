<?php
namespace XAF\http;

use PHPUnit\Framework\TestCase;
use Phake;
use org\bovigo\vfs\vfsStream;;

/**
 * @covers \XAF\http\FileSender
 */
class FileSenderTest extends TestCase
{
	/** @var FileSender */
	private $object;

	/** @var ResponseHeaderSetter */
	private $headerSetterMock;

	const FILE_CONTENTS = 'Yes sir, I am the file to be sent by the FileSender';

	static private $fileToBeSent;

	protected function setUp(): void
	{
		vfsStream::setup('work');
		self::$fileToBeSent = vfsStream::url('work') . '/file_to_be_sent';
		\file_put_contents(self::$fileToBeSent, self::FILE_CONTENTS);

		$this->headerSetterMock = Phake::mock(ResponseHeaderSetter::class);
		$this->object = new FileSender($this->headerSetterMock);

		unset($_SERVER['HTTP_IF_NONE_MATCH']);
		unset($_SERVER['HTTP_IF_MODIFIED_SINCE']);
	}

	public function testSendFileSendsFileContentsByDefault()
	{
		$this->callSendFileAndAssertFileIsOutput();
	}

	public function testSendFileSendsNothingIfEtagHeaderMatches()
	{
		$_SERVER['HTTP_IF_NONE_MATCH'] = $this->createEtagLikeFileSenderDoes();

		$this->callSendFileAndAssertNothingIsOutput();
	}

	public function testSendFileSendsFileContentsIfEtagHeaderDoesNotMatch()
	{
		$_SERVER['HTTP_IF_NONE_MATCH'] = 'some-garbage';

		$this->callSendFileAndAssertFileIsOutput();
	}

	public function testSendFileSendsNothingIfModifiedSinceHeaderIsCurrent()
	{
		$currentHttpTimestamp = \gmdate('D, d M Y H:i:s') . ' GMT';
		$_SERVER['HTTP_IF_MODIFIED_SINCE'] = $currentHttpTimestamp;

		$this->callSendFileAndAssertNothingIsOutput();
	}

	public function testSendFileSendsFileContentsIfModifiedSinceHeaderIsOld()
	{
		$timestampBeforeFileToSend = \filemtime(self::$fileToBeSent) - 3600;
		$oldHttpTimestamp = \gmdate('D, d M Y H:i:s', $timestampBeforeFileToSend) . ' GMT';
		$_SERVER['HTTP_IF_MODIFIED_SINCE'] = $oldHttpTimestamp;

		$this->callSendFileAndAssertFileIsOutput();
	}

	public function testCorrectContentHeadersAreSetWhenFileIsSent()
	{
		$this->object->setHttpCacheLifetimeSeconds(666);
		$this->object->setAllowPublicCaching(true);
		// We don't actually want to verify the output, but do this to suppress the file contents in the PHPUnit output
		$this->expectOutputString(self::FILE_CONTENTS);

		$this->object->sendFile(self::$fileToBeSent, 'mime/type');

		Phake::verify($this->headerSetterMock)->setContentType('mime/type');
		Phake::verify($this->headerSetterMock)->setContentLength(\strlen(self::FILE_CONTENTS));
		Phake::verify($this->headerSetterMock)->setCacheability(666, true);
		Phake::verify($this->headerSetterMock)->setLastModified(\filemtime(self::$fileToBeSent));
		Phake::verify($this->headerSetterMock)->setETag($this->createEtagLikeFileSenderDoes());
		Phake::verifyNoFurtherInteraction($this->headerSetterMock);
	}

	public function testOnlyNotModifiedHeaderIsSetWhenFileIsNotSent()
	{
		$this->object->setHttpCacheLifetimeSeconds(666);
		$this->object->setAllowPublicCaching(true);
		$_SERVER['HTTP_IF_NONE_MATCH'] = $this->createEtagLikeFileSenderDoes();

		$this->object->sendFile(self::$fileToBeSent, 'mime/type');

		Phake::verify($this->headerSetterMock)->setNotModified();
		Phake::verifyNoFurtherInteraction($this->headerSetterMock);
	}

	private function callSendFileAndAssertFileIsOutput()
	{
		$this->expectOutputString(self::FILE_CONTENTS);
		$this->object->sendFile(self::$fileToBeSent, 'mime/type');
	}

	private function callSendFileAndAssertNothingIsOutput()
	{
		$this->expectOutputString('');
		$this->object->sendFile(self::$fileToBeSent, 'mime/type');
	}

	private function createEtagLikeFileSenderDoes()
	{
		return \md5(self::$fileToBeSent . '@' . \filemtime(self::$fileToBeSent));
	}
}
