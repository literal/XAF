<?php
namespace XAF\view\helper;

require_once __DIR__ . '/DateTimeHelperTestBase.php';

/**
 * DateTimeHelperDe does not implement extra functionality over DateTimeHelper, so this is just a brief test
 * that the local definitions are present
 *
 * @covers \XAF\view\helper\DateTimeHelperDe
 */
class DateTimeHelperDeTest extends DateTimeHelperTestBase
{
	protected function setUp(): void
	{
		$this->object = new DateTimeHelperDe(static::TIMEZONE);
	}

	static public function getDateFormatTestTuples()
	{
		return [

			// [r]elative date if applicable, normal date otherwise
			['2010-05-29', 'r', '29.05.2010'],
			['2010-05-30', 'r', 'vorgestern'],
			['2010-05-31', 'r', 'gestern'],
			['2010-06-01', 'r', 'heute'],
			['2010-06-02', 'r', 'morgen'],
			['2010-06-03', 'r', 'übermorgen'],
			['2010-06-04', 'r', '04.06.2010'],

			// prepend "[o]n" unless relative day
			['2010-05-30', 'o', 'am 30.05.2010'],

			// abbreviated [w]eekday
			['2010-05-31', 'w', 'Mo., 31.05.2010'],
			['2010-06-06', 'w', 'So., 06.06.2010'],

			// full [W]eekday
			['2010-05-31', 'W', 'Montag, 31.05.2010'],
			['2010-06-06', 'W', 'Sonntag, 06.06.2010'],

			// abbreviated [m]onth name
			['2010-01-01', 'm', '1. Jan. 2010'],
			['2010-12-30', 'm', '30. Dez. 2010'],

			// full [M]onth name
			['2010-01-01', 'M', '1. Januar 2010'],
			['2010-12-30', 'M', '30. Dezember 2010'],

			// The full monty
			['2010-06-01', 'uoWM', 'Am Dienstag, 1. Juni 2010'],
		];
	}

	static public function getTimeFormatTestTuples()
	{
		return [
			// with o'[c]lock
			['12:00 ' . self::TIMEZONE, 'c', '12:00 Uhr'],

			// with time [z]one
			['2012-01-01 12:00 ' . self::TIMEZONE, 'z', '12:00 MEZ'],
			['2012-06-01 12:00 ' . self::TIMEZONE, 'z', '12:00 MESZ'],
		];
	}

	static public function getDateAndTimeFormatTestTuples()
	{
		return [
			// separate with "[a]t"
			[self::NOW, 'a', '01.06.2010 um 12:00'],

			// all date and time option chars can be used
			[self::NOW, 'uoMWascz', 'Am Dienstag, 1. Juni 2010 um 12:00:00 Uhr MESZ'],
		];
	}

	static public function getPartialDateTestTuples()
	{
		return [
			['1968-02-29', '', '29.02.1968'],

			// limit output to m[o]nth
			['1968-02-29', 'o', '02.1968'],

			// use abbreviated [m]onth name
			['1968-02-29', 'm', '29. Feb. 1968'],
			['1968-02', 'm', 'Feb. 1968'],

			// use full [M]onth name
			['1968-02-29', 'M', '29. Februar 1968'],
			['1968-02', 'M', 'Februar 1968'],
		];
	}

	static public function getDurationFormatTestTuples()
	{
		return [
			// format to [s]econd precision in [e]xtended format with [a]bbreviated unit names
			[3 * 60 * 60 + 6 * 60 + 45, 'sea', '3 Std. 6 Min. 45 Sek.'],
			[60 * 60 + 60 + 1, 'sea', '1 Std. 1 Min. 1 Sek.'],

			// format to [s]econd precision in [e]xtended format with [f]ull unit names
			[3 * 60 * 60 + 6 * 60 + 45, 'sef', '3 Stunden 6 Minuten 45 Sekunden'],
			[60 * 60 + 60 + 1, 'sef', '1 Stunde 1 Minute 1 Sekunde']
		];
	}
}
