<?php
namespace XAF\mail;

interface Mailer
{
	/**
	 * @param Mail $mail
	 */
	public function send( Mail $mail );
}
