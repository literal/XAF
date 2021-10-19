<?php
namespace XAF\helper;

use XAF\type\DateRange;
use DateTime;

class DateRangeHelper
{
	/**
	 * Create a date range covering a particular year from the first second of the first day
	 * to the last second of the last day
	 *
	 * @param int $year
	 * @return DateRange
	 */
	static public function createDateRangeForYear( $year )
	{
		return self::createDateRangeFromStartDateAndInterval(
			new DateTime(\sprintf('%04u-01-01', $year)),
			'+1 year -1 second'
		);
	}

	/**
	 * Create a date range covering a particular month from the first second of the first day
	 * to the last second of the last day
	 *
	 * @param int $year
	 * @param int $month
	 * @return DateRange
	 */
	static public function createDateRangeForMonth( $year, $month )
	{
		return self::createDateRangeFromStartDateAndInterval(
			new DateTime(\sprintf('%04u-%02u-01', $year, $month)),
			'+1 month -1 second'
		);
	}

	/**
	 * Create a date range covering a particular week from the first second of the first day
	 * to the last second of the last day
	 *
	 * @param int $year
	 * @param int $weekNumber The ISO week number in the given year
	 * @return DateRange
	 */
	static public function createDateRangeForWeek( $year, $weekNumber )
	{
		return self::createDateRangeFromStartDateAndInterval(
			new DateTime(\sprintf('%04uW%02u', $year, $weekNumber)),
			'+1 week -1 second'
		);
	}

	/**
	 * @param DateTime $startDate
	 * @param string $interval interval string accepted by DateTime->modify()
	 * @return DateRange
	 */
	static private function createDateRangeFromStartDateAndInterval( DateTime $startDate, $interval )
	{
		$startDate = clone $startDate;
		$endDate = clone $startDate;
		$endDate->modify($interval);
		return new DateRange($startDate, $endDate);
	}
}
