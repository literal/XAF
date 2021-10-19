<?php
namespace XAF\view\helper;

require_once __DIR__ . '/DateTimeHelperTestBase.php';

/**
 * DateTimeHelperDe does not implement extra functionality over DateTimeHelper, so this is just a brief test
 * of the differences
 *
 * @covers \XAF\view\helper\DateTimeHelperEnGb
 */
class DateTimeHelperEnGbTest extends DateTimeHelperTestBase
{
	protected function setUp(): void
	{
		$this->object = new DateTimeHelperEnGb(self::TIMEZONE);
	}

	static public function getDateFormatTestTuples()
	{
		return [
			['2010-05-30', '', '30/05/2010'],

			// abbreviated [m]onth name
			['2010-01-01', 'm', '1 Jan 2010'],

			// full [M]onth name
			['2010-01-01', 'M', '1 January 2010'],
		];
	}

	static public function getTimeFormatTestTuples()
	{
		return [
			['03:30 ' . self::TIMEZONE, 'c', '03:30'],
			['21:09 ' . self::TIMEZONE, 'c', '21:09'],
		];
	}

	static public function getDateAndTimeFormatTestTuples()
	{
		return [
			// separate with "[a]t"
			[self::NOW, 'a', '01/06/2010 at 12:00'],

			// all date and time option chars can be used
			[self::NOW, 'uoMWascz', 'On Tuesday, 1 June 2010 at 12:00:00 CEST'],
		];
	}

	static public function getPartialDateTestTuples()
	{
		return [
			['1968-02-29', '', '29/02/1968'],

			// limit output to m[o]nth
			['1968-02-29', 'o', '02/1968'],
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
