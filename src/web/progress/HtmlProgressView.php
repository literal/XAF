<?php
namespace XAF\web\progress;

use XAF\http\HeaderSender;
use XAF\view\helper\AssetHelper;
use XAF\view\helper\HtmlHelper;

class HtmlProgressView
{
	/** @var HeaderSender */
	private $headerSender;

	/** @var AssetHelper */
	private $assetHelper;

	/** @var array */
	private $cssFiles = [];

	/** @var HtmlHelper */
	private $htmlHelper;

	/** @var bool */
	private $isListOpen = false;

	/** @var bool */
	private $isItemOpen = false;

	/** @var int */
	private $messagePaddingBytes = 0;

	/** @var string */
	private $outputBuffer = '';

	public function __construct( HeaderSender $headerSender, AssetHelper $assetHelper, HtmlHelper $htmlHelper )
	{
		$this->headerSender = $headerSender;
		$this->assetHelper = $assetHelper;
		$this->htmlHelper = $htmlHelper;
	}

	/**
	 * Set number of bytes of padding after every message.
	 *
	 * Padding is done by appending an HTML comment after the message. It's a work-around for server setups, where
	 * output will only be sent to the client in larger chunks when some buffer is full.
	 *
	 * The number of bytes should equal the server's send buffer size. Worst case: the last byte of a message is
	 * stored in the first byte of a new server buffer. It would then take a whole buffer full of padding to fill
	 * and thus flush that buffer.
	 *
	 * @param int $bytes
	 */
	public function setMessagesPaddingBytes( $bytes )
	{
		$this->messagePaddingBytes = $bytes;
	}

	/**
	 * Set external CSS files to include in HTML document head
	 *
	 * @param array $cssFiles List of URL paths relative to the root path
	 */
	public function setCssFiles( $cssFiles = [] )
	{
		$this->cssFiles = $cssFiles;
	}

	public function start()
	{
		$this->setHeaders();
		$this->closeOutputBuffers();
		$this->putDocumentHead();
	}

	protected function closeOutputBuffers()
	{
		while( \ob_get_level() > 0 )
		{
			\ob_end_flush();
		}
	}

	protected function setHeaders()
	{
		$this->headerSender->setHeader('Content-Type', 'text/html; charset=utf-8');
		$this->headerSender->setHeader('Cache-Control', 'private, no-store, no-cache, must-revalidate');
		// Disable FASTCGI response buffering and GZIP on nginx
		$this->headerSender->setHeader('X-Accel-Buffering', 'no');
	}

	protected function putDocumentHead()
	{
		$this->output(
			'<!DOCTYPE HTML>' . "\n" .
			'<html>' . "\n" .
			'<head>' . "\n" .
			'	<title></title>' .
			'	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' . "\n" .
			'	<script type="text/javascript">' . "\n" .
			'	function scrollDown()' . "\n" .
			'	{' . "\n" .
			'		window.scrollTo(0, 9999999);' . "\n" .
			'	}' . "\n" .
			'	</script>' . "\n"
		);
		foreach( $this->cssFiles as $cssFile )
		{
			$this->putStylesheetLink($cssFile);
		}
		$this->output(
			'</head>' . "\n" .
			'<body>' . "\n"
		);
		$this->flushOutput();
	}

	/**
	 * @param string $cssFile
	 */
	protected function putStylesheetLink( $cssFile )
	{
		$this->output(
			'	<link rel="stylesheet" type="text/css" href="' .
			$this->htmlEscape($this->assetHelper->getUrl($cssFile)) .
			'">' . "\n"
		);
	}

	/**
	 * @param string $text
	 * @param int $level
	 */
	public function putHeading( $text, $level )
	{
		$this->closeListIfOpen();
		$tag = 'h' . $level;
		$this->output('<' . $tag . '>' . $this->htmlEscape($text) . '</' . $tag . '>' . "\n");
		$this->flushOutput();
	}

	/**
	 * @param string $message
	 * @param string|null $styleClass
	 */
	public function putStep( $message, $styleClass = null )
	{
		$this->openNewItem($styleClass);
		$this->output($this->htmlEscape($message));
		$this->flushOutput();
	}

	public function putTick()
	{
		$this->openItemIfNotOpen();
		$this->output('<wbr>.');
		$this->flushOutput();
	}

	/**
	 * @param string $message
	 * @param string|null $styleClass
	 */
	public function putConclusion( $message, $styleClass = null )
	{
		$this->closeListIfOpen();
		$this->output(
			'<p' . ($styleClass !== null ? ' class="' . $styleClass . '"' : '') . '>' .
			$this->htmlEscape($message) .
			'</p>' . "\n"
		);
		$this->flushOutput();
	}

	public function finish()
	{
		$this->closeListIfOpen();
		$this->output(
			'</body>' . "\n" .
			'</html>'
		);
		$this->flushOutput();
	}

	/**
	 * @param string|null $styleClass
	 */
	protected function openNewItem( $styleClass = null )
	{
		$this->closeItemIfOpen();
		$this->openItem($styleClass);
	}

	protected function openItemIfNotOpen()
	{
		if( !$this->isItemOpen )
		{
			$this->openItem();
		}
	}

	/**
	 * @param string|null $styleClass
	 */
	protected function openItem( $styleClass = null )
	{
		$this->openListIfNotOpen();
		$this->output('<li' . ($styleClass !== null ? ' class="' . $styleClass . '"' : '') . '>');
		$this->isItemOpen = true;
	}

	protected function closeItemIfOpen()
	{
		if( $this->isItemOpen )
		{
			$this->closeItem();
		}
	}

	protected function closeItem()
	{
		$this->output('</li>' . "\n");
		$this->isItemOpen = false;
	}

	protected function openListIfNotOpen()
	{
		if( !$this->isListOpen )
		{
			$this->openList();
		}
	}

	protected function closeListIfOpen()
	{
		if( $this->isListOpen )
		{
			$this->closeList();
		}
	}

	protected function openList()
	{
		$this->output('<ul>' . "\n");
		$this->isListOpen = true;
	}

	protected function closeList()
	{
		$this->closeItemIfOpen();
		$this->output('</ul>' . "\n");
		$this->isListOpen = false;
	}

	/**
	 * @param string $value
	 * @return string
	 */
	protected function htmlEscape( $value )
	{
		return $this->htmlHelper->escape($value);
	}

	/**
	 * @param string $value
	 */
	protected function output( $value )
	{
		$this->outputBuffer .= $value;
	}

	protected function flushOutput()
	{
		$this->putJsStatement('scrollDown();');
		$this->addPaddingToOutputBuffer();
		echo $this->outputBuffer;
		$this->outputBuffer = '';
		\flush();
	}

	protected function addPaddingToOutputBuffer()
	{
		if( $this->messagePaddingBytes > 0 )
		{
			$spacesCount = \max(1, $this->messagePaddingBytes - 8);
			$this->outputBuffer .= '<!--' . \str_repeat(' ', $spacesCount) . '-->';
		}
	}

	public function putJsStatement( $jsStatement )
	{
		$this->output('<script type="text/javascript">' . $jsStatement . '</script>' . "\n");
	}
}
