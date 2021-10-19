<?php
namespace XAF\type;

use DateTime;

/**
 * Represent a time period which may optionally be half-open or undefined.
 */
class TimeRange
{
	/** @var DateTime **/
	protected $start;

	/** @var DateTime **/
	protected $end;

	/**
	 * @param DateTime|string|null $start
	 * @param DateTime|string|null $end
	 */
	public function __construct( $start = null, $end = null )
	{
		$this->setStart($start);
		$this->setEnd($end);
	}

	/**
	 * @return DateTime
	 */
	public function getStart()
	{
		return $this->start ? clone $this->start : null;
	}

	/**
	 * @param DateTime|string|null $start
	 */
	public function setStart( $start = null )
	{
		$this->start = $this->convertToDateTimeIfNotNull($start);
	}

	/**
	 * @return DateTime
	 */
	public function getEnd()
	{
		return $this->end ? clone $this->end : null;
	}

	/**
	 * @param DateTime|string|null $end
	 */
	public function setEnd( $end = null )
	{
		$this->end = $this->convertToDateTimeIfNotNull($end);
	}

	/**
	 * @param DateTime|string|int|null $value
	 * @return DateTime|null
	 */
	private function convertToDateTimeIfNotNull( $value )
	{
		switch( true )
		{
			case \is_int($value) || \ctype_digit($value):
				return new DateTime('@' . $value);

			case \is_string($value):
				return new DateTime($value);

			case $value instanceof DateTime:
				return clone $value;
		}
		return null;
	}

	/**
	 * @return bool
	 */
	public function isClosed()
	{
		return $this->start && $this->end;
	}
}
