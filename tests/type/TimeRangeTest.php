<?php
namespace XAF\type;

use PHPUnit\Framework\TestCase;
use DateTime;

/**
 * @covers \XAF\type\TimeRange
 */
class TimeRangeTest extends TestCase
{
	public function testValuesCanBeSetAsStrings()
	{
		$object = new TimeRange('2010-11-01 09:10:11', '2011-03-01 23:54:58');

		$this->assertTimeRangeIs('2010-11-01 09:10:11', '2011-03-01 23:54:58', $object);
	}

	public function testValuesCanBeSetAsDateTimeObjects()
	{
		$object = new TimeRange(new DateTime('2010-11-01 12:34:56'), new DateTime('2011-03-01 01:23:45'));

		$this->assertTimeRangeIs('2010-11-01 12:34:56', '2011-03-01 01:23:45', $object);
	}

	public function testValuesCanBeSetAsUnixTimestamps()
	{
		$start = new DateTime('2010-10-31 03:33:01');
		$end = new DateTime('2011-03-01 16:40:48');
		$object = new TimeRange(\strval($start->getTimestamp()), \intval($end->getTimestamp()));

		$this->assertTimeRangeIs('2010-10-31 03:33:01', '2011-03-01 16:40:48', $object);
	}

	public function testDatesAreNotAffectedByChangesToSourceObjects()
	{
		$startDateSource = new DateTime('2015-01-01 12:34:56');
		$endDateSource = new DateTime('2015-02-01 01:23:45');
		$object = new TimeRange($startDateSource, $endDateSource);

		// If the objects passed to the constructor weren't cloned, this would affect the date range
		$startDateSource->setDate(2020, 10, 18);
		$endDateSource->setDate(2020, 10, 19);

		$this->assertTimeRangeIs('2015-01-01 12:34:56', '2015-02-01 01:23:45', $object);
	}

	public function testDatesAreNotAffectedByChangesToResultObjects()
	{
		$object = new TimeRange('2015-01-01 12:34:56', '2015-02-01 01:23:45');
		$startDateResult = $object->getStart();
		$endDateResult = $object->getEnd();

		// If the objects returned by the getters weren't cloned, their modification would affect the
		// dates stored in the date range
		$startDateResult->setDate(2020, 10, 18);
		$endDateResult->setDate(2020, 10, 19);

		$this->assertTimeRangeIs('2015-01-01 12:34:56', '2015-02-01 01:23:45', $object);
	}

	public function testDateRangeAllowsOpenRanges()
	{
		$object = new TimeRange(null, 'now');
		$this->assertNull($object->getStart());

		$object = new TimeRange('now');
		$this->assertNull($object->getEnd());
	}

	public function testRangeIsClosedWhenStartAndEndDateAreSet()
	{
		$object = new TimeRange('2015-01-01 12:00:00', '2015-02-01 12:00:00');

		$this->assertTrue($object->isClosed());
	}

	public function testRangeIsNotClosedWhenStartDateIsMissing()
	{
		$object = new TimeRange(null, '2015-02-01 12:00:00');

		$this->assertFalse($object->isClosed());
	}

	public function testRangeIsNotClosedWhenEndDateIsMissing()
	{
		$object = new TimeRange('2015-02-01 12:00:00', null);

		$this->assertFalse($object->isClosed());
	}

	private function assertTimeRangeIs( $start, $end, TimeRange $range )
	{
		$this->assertEquals((new DateTime($start))->getTimestamp(), $range->getStart()->getTimestamp());
		$this->assertEquals((new DateTime($end))->getTimestamp(), $range->getEnd()->getTimestamp());
	}
}
