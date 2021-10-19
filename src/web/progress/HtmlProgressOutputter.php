<?php
namespace XAF\web\progress;

use XAF\progress\Outputter;
use XAF\view\helper\LanguageSpecificTranslator as Translator;
use XAF\helper\MessageRenderer;
use XAF\type\Message;
use XAF\progress\SectionHead;
use XAF\progress\Conclusion;
use XAF\progress\DebugInfo;
use XAF\progress\Tick;

/**
 * Receives progress messages from services performing complex, lengthy tasks and
 * forwards them to the progress view for immediate, progressive output to the client.
 */
class HtmlProgressOutputter implements Outputter
{
	/** @var HtmlProgressView */
	protected $view;

	/** @var Translator */
	protected $translator;

	/** @var string|null */
	protected $translationTable;

	private $showDebugInfo = false;

	function __construct( HtmlProgressView $view, Translator $translator )
	{
		$this->view = $view;
		$this->translator = $translator;
	}

	/**
	 * Set translation table for all messages. If not set, messages will be output as received.
	 *
	 * @param string|null $translationTable
	 */
	public function setTranslationTable( $translationTable )
	{
		$this->translationTable = $translationTable;
	}

	/**
	 * @param bool $showDebugInfo
	 */
	function setShowDebugInfo( $showDebugInfo )
	{
		$this->showDebugInfo = $showDebugInfo;
	}

	public function start()
	{
		$this->view->start();
	}

	public function notify( Message $message )
	{
		switch( true )
		{
			case $message instanceof SectionHead:
				$this->putHeading($message);
				break;

			case $message instanceof Tick:
				$this->putTick();
				break;

			case $message instanceof Conclusion:
				$this->putConclusion($message);
				break;

			case $message instanceof DebugInfo:
				$this->putDebugInfo($message);
				break;

			default:
				$this->putStep($message);
				break;
		}
	}

	/**
	 * @param string|null $jsCall JS statement(s) to run - usually to enable some sort of "continue" button and/or
	 *     stop a progress indicator
	 */
	public function finish( $jsCall = null )
	{
		if( $jsCall !== null )
		{
			$this->view->putJsStatement($jsCall);
		}
		$this->view->finish();
	}

	protected function putHeading( SectionHead $message )
	{
		$this->view->putHeading($this->translate($message->getText(), $message->getParams()), $message->getLevel());
	}

	protected function putStep( Message $message )
	{
		$this->view->putStep(
			$this->translate($message->getText(), $message->getParams()),
			$this->getCssClassForMessage($message)
		);
	}

	protected function putTick()
	{
		$this->view->putTick();
	}

	protected function putDebugInfo( Message $message )
	{
		if( $this->showDebugInfo )
		{
			$this->view->putStep($this->translate($message->getText(), $message->getParams()), 'debug');
		}
	}

	protected function putConclusion( Conclusion $message )
	{
		$this->view->putConclusion(
			$this->translate($message->getText(), $message->getParams()),
			'conclusion ' . $this->getCssClassForMessage($message)
		);
	}

	/**
	 * @param string $text
	 * @param array $params
	 * @return string
	 */
	protected function translate( $text, array $params = [] )
	{
		return $this->translationTable !== null
			? $this->translator->translate($text, $this->translationTable, $params)
			: MessageRenderer::render($text, $params);
	}

	/**
	 * @param Message $message
	 * @return string|null
	 */
	protected function getCssClassForMessage( Message $message )
	{
		switch( $message->getStatus() )
		{
			case Message::STATUS_SUCCESS:
				return 'success';

			case Message::STATUS_WARNING:
				return 'warning';

			case Message::STATUS_ERROR:
				return 'error';
		}
		return null;
	}
}
