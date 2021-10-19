<?php
namespace XAF\helper;

class MathHelper
{
	/**
	 * @param mixed $value
	 * @param mixed $minimum
	 * @param mixed $maximum
	 * @return mixed
	 */
	static public function limit( $value, $minimum = null, $maximum = null )
	{
		if( $minimum !== null && $value < $minimum )
		{
			return $minimum;
		}
		if( $maximum !== null && $value > $maximum )
		{
			return $maximum;
		}
		return $value;
	}
}
