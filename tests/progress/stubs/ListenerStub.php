<?php
namespace XAF\progress;

use XAF\type\Message;

class ListenerStub implements Listener
{
	/** @var Message[] */
	public $messages = [];

	public function notify( Message $message )
	{
		$this->messages[] = $message;
	}
}
