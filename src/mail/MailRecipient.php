<?php
namespace XAF\mail;

class MailRecipient
{
	CONST TYPE_TO = 'to';
	CONST TYPE_CC = 'cc';
	CONST TYPE_BCC = 'bcc';

	public function __construct( $address, $name = null, $type = self::TYPE_TO )
	{
		$this->address = $address;
		$this->name = $name;
		$this->type = $type;
	}

	/** @var string Any of the TYPE_* constants */
	public $type;

	/** @var string */
	public $address;

	/** @var string|null */
	public $name;
}
