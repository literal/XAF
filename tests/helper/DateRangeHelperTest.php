<?php
namespace XAF\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\helper\DateRangeHelper
 */
class DateRangeHelperTest extends TestCase
{
	public function testCreateYearDateRange()
	{
		$dateRange = DateRangeHelper::createDateRangeForYear(2017);

		$this->assertEquals('2017-01-01 00:00:00', $dateRange->getStart()->format('Y-m-d H:i:s'));
		$this->assertEquals('2017-12-31 23:59:59', $dateRange->getEnd()->format('Y-m-d H:i:s'));
	}

	public function testCreateYearDateRangeForLeapYear()
	{
		$dateRange = DateRangeHelper::createDateRangeForYear(2016);

		$this->assertEquals('2016-01-01 00:00:00', $dateRange->getStart()->format('Y-m-d H:i:s'));
		$this->assertEquals('2016-12-31 23:59:59', $dateRange->getEnd()->format('Y-m-d H:i:s'));
	}

	public function testCreateMonthDateRange()
	{
		$dateRange = DateRangeHelper::createDateRangeForMonth(2010, 12);

		$this->assertEquals('2010-12-01 00:00:00', $dateRange->getStart()->format('Y-m-d H:i:s'));
		$this->assertEquals('2010-12-31 23:59:59', $dateRange->getEnd()->format('Y-m-d H:i:s'));
	}

	public function testCreateMonthDateRangeForFebruary()
	{
		$dateRange = DateRangeHelper::createDateRangeForMonth(2005, 02);

		$this->assertEquals('2005-02-01 00:00:00', $dateRange->getStart()->format('Y-m-d H:i:s'));
		$this->assertEquals('2005-02-28 23:59:59', $dateRange->getEnd()->format('Y-m-d H:i:s'));
	}

	public function testCreateWeekDateRange()
	{
		$dateRange = DateRangeHelper::createDateRangeForWeek(2010, 50);

		$this->assertEquals('2010-12-13 00:00:00', $dateRange->getStart()->format('Y-m-d H:i:s'));
		$this->assertEquals('2010-12-19 23:59:59', $dateRange->getEnd()->format('Y-m-d H:i:s'));
	}

	/**
	 * ISO-8601 date
	 * WIKIPEDIA -------
	 * Jeden Montag und nur montags beginnt eine neue Kalenderwoche.
     * Die erste Kalenderwoche ist diejenige, die mindestens 4 Tage des neuen Jahres enthält.
	 * ----------
	 */

	public function testWeekBeginsOnMonday()
	{
		$week = DateRangeHelper::createDateRangeForWeek(2011, 13);

		$firstWeekDay = $week->getStart()->format('l');

		$this->assertEquals('Monday', $firstWeekDay);
	}

	public function testFirstWeekMustHaveAtLeast4DaysInNewYear()
	{
		$weekWith4DaysInNewYear = DateRangeHelper::createDateRangeForWeek(2009, 1);
		$weekWith3DaysInNewYear = DateRangeHelper::createDateRangeForWeek(2010, 1);

		$this->assertEquals('2008', $weekWith4DaysInNewYear->getStart()->format('Y'));
		$this->assertEquals('2010', $weekWith3DaysInNewYear->getStart()->format('Y'));
	}

}
