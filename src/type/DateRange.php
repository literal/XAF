<?php
namespace XAF\type;

/**
 * Special case of time range where only dates are used and times are always set in a way that the rage spans whole days
 */
class DateRange extends TimeRange
{
	public function setStart( $start = null )
	{
		parent::setStart($start);
		if( $this->start )
		{
			$this->start->setTime(0, 0, 0);
		}
	}

	public function setEnd( $end = null )
	{
		parent::setEnd($end);
		if( $this->end )
		{
			$this->end->setTime(23, 59, 59);
		}
	}
}
