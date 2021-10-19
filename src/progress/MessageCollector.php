<?php
namespace XAF\progress;

use XAF\type\Message;

class MessageCollector implements Listener
{
	private $messages = [];

	public function notify( Message $message )
	{
		$this->messages[] = $message;
	}

	public function reset()
	{
		$this->messages = [];
	}

	/**
	 * @return Message[]
	 */
	public function getMessages()
	{
		return $this->messages;
	}
}
