<?php
namespace XAF\progress;

use XAF\type\Message;

/**
 * Unspecified progress message.
 *
 * Indicates that some piece of a lengthy operation has completed.
 *
 * Receivers of this message might just ignore it, move a progress bar forward, print a dot or whatever.
 */
class Tick extends Message
{
	public function __construct()
	{
		parent::__construct('.');
	}
}
