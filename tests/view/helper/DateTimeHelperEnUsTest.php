<?php
namespace XAF\view\helper;

require_once __DIR__ . '/DateTimeHelperTestBase.php';

/**
 * DateTimeHelperDe does not implement extra functionality over DateTimeHelper, so this is just a brief test
 * of the differences
 *
 * @covers \XAF\view\helper\DateTimeHelperEnUs
 */
class DateTimeHelperEnUsTest extends DateTimeHelperTestBase
{
	protected function setUp(): void
	{
		$this->object = new DateTimeHelperEnUs(self::TIMEZONE);
	}

	static public function getDateFormatTestTuples()
	{
		return [

			['2010-05-30', '', '5/30/2010'],

			// abbreviated [m]onth name
			['2010-01-01', 'm', 'Jan 1, 2010'],

			// full [M]onth name
			['2010-01-01', 'M', 'January 1, 2010'],
		];
	}

	static public function getTimeFormatTestTuples()
	{
		return [
			// test am/pm
			['00:00 ' . self::TIMEZONE, 'c', '12:00 am'],
			['03:30 ' . self::TIMEZONE, 'c', '3:30 am'],
			['12:00 ' . self::TIMEZONE, 'c', '12:00 pm'],
			['21:09 ' . self::TIMEZONE, 'c', '9:09 pm'],
		];
	}

	static public function getDateAndTimeFormatTestTuples()
	{
		return [
			// separate with "[a]t"
			[self::NOW, 'a', '6/1/2010 at 12:00 pm'],

			// all date and time option chars can be used
			[self::NOW, 'uoMWascz', 'On Tuesday, June 1, 2010 at 12:00:00 pm CEST'],
		];
	}

	static public function getPartialDateTestTuples()
	{
		return [
			['1968-02-29', '', '2/29/1968'],

			// limit output to m[o]nth
			['1968-02-29', 'o', '2/1968'],
		];
	}

	static public function getDurationFormatTestTuples()
	{
		return [
			// format to [s]econd precision in [e]xtended format with [a]bbreviated unit names
			[3 * 60 * 60 + 6 * 60 + 45, 'sea', '3 hrs 6 min 45 sec'],
			[60 * 60 + 60 + 1, 'sea', '1 hr 1 min 1 sec'],

			// format to [s]econd precision in [e]xtended format with [f]ull unit names
			[3 * 60 * 60 + 6 * 60 + 45, 'sef', '3 hours 6 minutes 45 seconds'],
			[60 * 60 + 60 + 1, 'sef', '1 hour 1 minute 1 second']
		];
	}
}
