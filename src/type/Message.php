<?php
namespace XAF\type;

class Message
{
	const STATUS_NONE = 0;
	const STATUS_SUCCESS = 1;
	const STATUS_WARNING = 2;
	const STATUS_ERROR = 3;

	/** @var int */
	protected $status = self::STATUS_NONE;

	/** @var string */
	protected $text = '';

	/** @var array */
	protected $params = [];

	/**
	 * @param string $text
	 * @param array $params
	 * @param int $status
	 */
	public function __construct( $text, array $params = [], $status = self::STATUS_NONE )
	{
		$this->text = $text;
		$this->params = $params;
		$this->status = $status;
	}

	/**
	 * @return int
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @return string
	 */
	public function getText()
	{
		return $this->text;
	}

	/**
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}
}
