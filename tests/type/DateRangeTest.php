<?php
namespace XAF\type;

use PHPUnit\Framework\TestCase;
use DateTime;

/**
 * @covers \XAF\type\DateRange<extended>
 */
class DateRangeTest extends TestCase
{
	public function testValuesCanBeSetAsStrings()
	{
		$object = new DateRange('2010-11-01', '2011-03-01');

		$this->assertDateRangeIs('2010-11-01 00:00:00', '2011-03-01 23:59:59', $object);
	}

	public function testValuesCanBeSetAsDateTimeObjects()
	{
		$object = new DateRange(new DateTime('2010-11-01'), new DateTime('2011-03-01'));

		$this->assertDateRangeIs('2010-11-01 00:00:00', '2011-03-01 23:59:59', $object);
	}

	public function testGetDateReturnsDateTimeObjectsWithTimeAdjustedToSpanWholeDays()
	{
		$object = new DateRange('2010-11-01 01:33:01', '2011-03-01 16:40:48');

		$this->assertDateRangeIs('2010-11-01 00:00:00', '2011-03-01 23:59:59', $object);
	}

	public function testDatesAreNotAffectedByChangesToSourceObjects()
	{
		$startDateSource = new DateTime('2015-01-01 12:00:00');
		$endDateSource = new DateTime('2015-02-01 12:00:00');
		$object = new DateRange($startDateSource, $endDateSource);

		// If the objects passed to the constructor weren't cloned, this would affect the date range
		$startDateSource->setDate(2020, 10, 18);
		$endDateSource->setDate(2020, 10, 19);

		$this->assertDateRangeIs('2015-01-01 00:00:00', '2015-02-01 23:59:59', $object);
	}

	public function testDatesAreNotAffectedByChangesToResultObjects()
	{
		$object = new DateRange('2015-01-01 12:00:00', '2015-02-01 12:00:00');
		$startDateResult = $object->getStart();
		$endDateResult = $object->getEnd();

		// If the objects returned by the getters weren't cloned, their modification would affect the
		// dates stored in the date range
		$startDateResult->setDate(2020, 10, 18);
		$endDateResult->setDate(2020, 10, 19);

		$this->assertDateRangeIs('2015-01-01 00:00:00', '2015-02-01 23:59:59', $object);
	}

	public function testDateRangeAllowsOpenRanges()
	{
		$object = new DateRange(null, 'now');
		$this->assertNull($object->getStart());

		$object = new DateRange('now');
		$this->assertNull($object->getEnd());
	}

	public function testDateRangeIsClosedWhenStartAndEndDateAreSet()
	{
		$object = new DateRange('2015-01-01 12:00:00', '2015-02-01 12:00:00');

		$this->assertTrue($object->isClosed());
	}

	public function testDateRangeIsNotClosedWhenStartDateIsMissing()
	{
		$object = new DateRange(null, '2015-02-01 12:00:00');

		$this->assertFalse($object->isClosed());
	}

	public function testDateRangeIsNotClosedWhenEndDateIsMissing()
	{
		$object = new DateRange('2015-02-01 12:00:00', null);

		$this->assertFalse($object->isClosed());
	}

	private function assertDateRangeIs( $start, $end, DateRange $dateRange )
	{
		$this->assertEquals($start, $dateRange->getStart()->format('Y-m-d H:i:s'));
		$this->assertEquals($end, $dateRange->getEnd()->format('Y-m-d H:i:s'));
	}
}
