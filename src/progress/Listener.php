<?php
namespace XAF\progress;

use XAF\type\Message;

interface Listener
{
	public function notify( Message $message );
}
