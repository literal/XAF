<?php
namespace XAF\progress;

use XAF\type\Message;

/**
 * Heading for a series of processing steps
 */
class SectionHead extends Message
{
	/** @var int */
	protected $level;

	/**
	 * @param string $name
	 * @param array $params
	 * @param int $level Hierachy level of the heading
	 */
	public function __construct( $name, array $params = [], $level = 2 )
	{
		parent::__construct($name, $params);
		$this->level = $level;
	}

	public function getLevel()
	{
		return $this->level;
	}
}
