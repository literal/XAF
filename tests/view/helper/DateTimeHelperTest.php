<?php
namespace XAF\view\helper;

use DateTime;

require_once __DIR__ . '/DateTimeHelperTestBase.php';

/**
 * @covers \XAF\view\helper\DateTimeHelper
 */
class DateTimeHelperTest extends DateTimeHelperTestBase
{
	protected function setUp(): void
	{
		$this->object = new DateTimeHelper(self::TIMEZONE);
	}

	// *********************************************************************************************
	// date
	// *********************************************************************************************

	static public function getDateFormatTestTuples()
	{
		return [
			// input can be a string
			[self::NOW, '', '2010-06-01'],
			// ...a DateTime object
			[new DateTime(self::NOW), '', '2010-06-01'],
			// ...or a Unix timestamp
			[self::NOW_TS, '', '2010-06-01'],

			// [r]elative date if applicable, normal date otherwise
			['2010-05-30', 'r', '2010-05-30'],
			['2010-05-31', 'r', 'yesterday'],
			['2010-06-01', 'r', 'today'],
			['2010-06-02', 'r', 'tomorrow'],
			['2010-06-03', 'r', '2010-06-03'],

			// prepend "[o]n" unless relative day
			['2010-05-30', 'ro', 'on 2010-05-30'],
			['2010-05-31', 'ro', 'yesterday'],
			['2010-05-31', 'o', 'on 2010-05-31'],

			// [u]pper-case first word
			['2010-05-31', 'ru', 'Yesterday'],
			['2010-06-01', 'ru', 'Today'],
			['2010-06-02', 'ru', 'Tomorrow'],
			['2010-06-01', 'ou', 'On 2010-06-01'],

			// abbreviated [w]eekday
			['2010-05-31', 'w', 'Mon, 2010-05-31'],
			['2010-06-03', 'w', 'Thu, 2010-06-03'],
			['2010-06-05', 'w', 'Sat, 2010-06-05'],

			// full [W]eekday
			['2010-06-01', 'W', 'Tuesday, 2010-06-01'],
			['2010-06-02', 'W', 'Wednesday, 2010-06-02'],
			['2010-06-06', 'W', 'Sunday, 2010-06-06'],

			// abbreviated [m]onth name
			['2010-01-01', 'm', '1 Jan 2010'],
			['2010-06-15', 'm', '15 Jun 2010'],
			['2010-11-30', 'm', '30 Nov 2010'],

			// full [M]onth name
			['2010-02-28', 'M', '28 February 2010'],
			['2010-05-03', 'M', '3 May 2010'],
			['2010-10-10', 'M', '10 October 2010'],

			// The full monty
			['2010-06-01', 'uoWM', 'On Tuesday, 1 June 2010'],

			// invalid inputs
			['foobar', '', ''],
			[null, '', ''],
			[true, '', ''],

			// dates outside 32-bit Unix timestamp range should be no problem
			['1547-01-10', '', '1547-01-10'],
			['2264-12-24', '', '2264-12-24'],
		];
	}

	public function testDateFormatDefaultValueCanBeOverridden()
	{
		$result = $this->object->formatDate('invalid', '', 'default');

		$this->assertSame('default', $result);
	}

	public function testDateFormatUsesCurrentTimeForRelativeDatesByDefault()
	{
		$result = $this->object->formatDate(\time(), 'r');

		$this->assertSame('today', $result);
	}

	public function testDateFormatCanHandleRelativeDatesOutsideUnixTimestampRange()
	{
		$result = $this->object->formatDate('1388-03-17', 'r', '', '1388-03-16');

		$this->assertSame('tomorrow', $result);
	}

	// *********************************************************************************************
	// time
	// *********************************************************************************************

	static public function getTimeFormatTestTuples()
	{
		return [
			// input can be a full date string
			[self::NOW, '', '12:00'],
			// ...just a time string
			['12:00 ' . self::TIMEZONE, '', '12:00'],
			// ...a DateTime object
			[new DateTime(self::NOW), '', '12:00'],
			// ...or a Unix timestamp
			[self::NOW_TS, '', '12:00'],

			// with [s]econds
			['12:00:30 ' . self::TIMEZONE, 's', '12:00:30'],

			// the o'[c]lock option should not be aplied in English
			['12:00 ' . self::TIMEZONE, 'c', '12:00'],

			// with time zone abbreviation (CET/CEST because TZ Europe/Berlin is set in setUp() method)
			[new DateTime('2016-01-21 10:00 UTC'), 'z', '11:00 CET'],
			[new DateTime('2016-06-21 10:00 UTC'), 'z', '12:00 CEST'],

			// and in combination
			['06:06:06 ' . self::TIMEZONE, 'cs', '06:06:06'],

			// boundary cases
			['24:00 ' . self::TIMEZONE, '', '00:00'],

			// invalid inputs
			['foobar', '', ''],
			[null, '', ''],
			[true, '', ''],

			// dates outside 32-bit Unix timestamp range should be no problem
			['1547-01-10 14:44 ' . self::TIMEZONE, '', '14:44'],
			['2264-12-24 03:03 ' . self::TIMEZONE, '', '03:03'],
		];
	}

	public function testTimeFormatDefaultValueCanBeOverridden()
	{
		$result = $this->object->formatTime('invalid', '', 'default');

		$this->assertSame('default', $result);
	}

	// *********************************************************************************************
	// combined date and time
	// *********************************************************************************************

	static public function getDateAndTimeFormatTestTuples()
	{
		return [
			// time and date are by default separated by space
			[self::NOW, '', '2010-06-01 12:00'],

			// separate with "[a]t"
			[self::NOW, 'a', '2010-06-01 at 12:00'],

			// all date and time option chars can be used
			[self::NOW, 'uoMWascz', 'On Tuesday, 1 June 2010 at 12:00:00 CEST'],

			// invalid inputs
			['foobar', '', ''],
			[null, '', ''],
			[true, '', ''],
		];
	}

	// *********************************************************************************************
	// duration
	// *********************************************************************************************

	static public function getDurationFormatTestTuples()
	{
		return [
			// default format with adaptive units and unit sign
			[30, '', '30 s'],
			[70, '', '1:10 min'],
			[3660, '', '1:01 h'],
			[3629, '', '1:00 h'],   // minutes shall be rounded if seconds are not used
			[3630, '', '1:01 h'],   // ...

			// explicit unit selection (capital lettér: leftmost unit, lower letter: rightmost unit)
			[30, 'h', '0 h'],
			[1800, 'h', '1 h'],      // hours shall be rounded if minutes are not used
			[60, 'Hm', '0:01 h'],
			[90, 'Hm', '0:02 h'],    // minutes shall be rounded if seconds are not used
			[90, 'Hs', '0:01:30 h'],
			[1, 'Hs', '0:00:01 h'],
			[600, 'Mm', '10 min'],
			[31, 'Ms', '0:31 min'],
			[7201, 'Ms', '120:01 min'], // no switch to hours although > 1 hour
			[7201, 'S', '7201 s'],

			// [n]umeric output only
			[3665, 'nHm', '1:01'],
			[3665, 'nHs', '1:01:05'],
			[70, 'nMm', '1'],
			[70, 'nMs', '1:10'],
			[70, 'nS', '70'],
			[3630, 'n', '1:01'],      // auto-unit (doesn't really make much sense with numeric)
			[61, 'n', '1:01'],        // auto-unit (doesn't really make much sense with numeric)

			// [e]xtended format
			[3660, 'e', '1 h 1 min'],
			[1800, 'me', '30 min'],
			[60, 'Hse', '0 h 1 min 0 s'],
			[30, 'e', '30 s'],
			[7322, 'Hsea', '2 hrs 2 min 2 sec'],        // with [a]bbreviated unit names
			[3661, 'Hsef', '1 hour 1 minute 1 second'], // with [f]ull unit names
			[3660, 'Hme', '1 h 1 min'],
			[30, 'he', '0 h'],
			[30, 'me', '1 min'],

			// [a]bbreviated and [f]ull unit names singular & plural
			[1800, 'Ha', '0:30 hrs'],
			[3600, 'Ha', '1:00 hr'],
			[7200, 'Ha', '2:00 hrs'],
			[1800, 'Hf', '0:30 hours'],
			[3600, 'Hf', '1:00 hour'],
			[7200, 'Hf', '2:00 hours'],
			[30, 'Ma', '0:30 min'],
			[60, 'Ma', '1:00 min'],
			[120, 'Ma', '2:00 min'],
			[30, 'Mf', '0:30 minutes'],
			[60, 'Mf', '1:00 minute'],
			[120, 'Mf', '2:00 minutes'],
			[0, 'Sa', '0 sec'],
			[1, 'Sa', '1 sec'],
			[2, 'Sa', '2 sec'],
			[0, 'Sf', '0 seconds'],
			[1, 'Sf', '1 second'],
			[2, 'Sf', '2 seconds'],

			// [i]SO 8601 format
			[4000, 'i', 'PT1H6M40S'],
			[3660, 'i', 'PT1H1M0S'],
			[360, 'i', 'PT0H6M0S'],
			[0, 'i', 'PT0H0M0S'],
			[25, 'Hmfni', 'PT0H0M25S'], // all other options are ignored

			// invalid inputs
			['18foobar', '', ''],
			[null, '', ''],
			['', '', ''],
			[true, '', ''],

			// Numeric string
			['30', '', '30 s'],

			// Make sure result is not "0:60 h" because total number of seconds is < one hour
			[60 * 60 - 1, 'Hm', '1:00 h'],
		];
	}

	public function testDurationFormatReturnsUnitByDefault()
	{
		$actualResult = $this->object->formatDuration(70);

		$this->assertEquals('1:10 min', $actualResult);
	}

	public function testDurationFormatAdjustsFormatDynamicallyByDefault()
	{
		$actualResult = $this->object->formatDuration(3600);

		$this->assertEquals('1:00 h', $actualResult);
	}

	// *********************************************************************************************
	// age
	// *********************************************************************************************

	static public function getAgeComputationTestTuples()
	{
		return [
			// Selection of unit
			['2020-01-01 11:58:30', '', '2020-01-01 12:00:00', 90],      // default unit is seconds
			['2020-01-01 11:59:39', 's', '2020-01-01 12:00:00', 21],     // seconds
			['2020-01-01 10:57:54', 'm', '2020-01-01 12:00:00', 62.1],   // minutes
			['2019-12-31 10:45:00', 'h', '2020-01-01 12:00:00', 25.25],  // hours
			['2019-12-31 06:00:00', 'd', '2020-01-01 12:00:00', 1.25],   // days
			['2020-01-01 00:00:00', 'smhd', '2020-01-01 12:00:00', 0.5], // largest unit (days) takes precedence

			// Negative result if value is in the future (i.e. later than reference value)
			['2020-01-01 12:00:04', 's', '2020-01-01 12:00:00', -4],
			['2020-01-01 12:04:00', 'm', '2020-01-01 12:00:00', -4],
			['2020-01-01 16:00:00', 'h', '2020-01-01 12:00:00', -4],
			['2020-01-05 12:00:00', 'd', '2020-01-01 12:00:00', -4],

			// Number of days is not limited by month or year boundaries
			['2019-01-01 12:00:00', 'd', '2020-01-01 12:00:00', 365],

			// Inputs can also be Unix timestamps or DateTime instances
			[1000000, 'h', 1003600, 1],
			[new DateTime('2010-06-21 00:00:00'), 'm', new DateTime('2010-06-21 00:29:30'), 29.5],
		];
	}

	/**
	 * @dataProvider getAgeComputationTestTuples
	 */
	public function testAgeComputation( $input, $options, $referenceValue, $expectedResult )
	{
		$actualResult = $this->object->computeAge($input, $options, $referenceValue);

		$this->assertEquals($expectedResult, $actualResult, '', 0.001);
	}

	public function testAgeComputationReferenceDefaultsToCurrentTime()
	{
		$result = $this->object->computeAge(new DateTime('-1 hour'), 'h');

		$this->assertGreaterThanOrEqual(1, $result);
		$this->assertLessThan(2, $result);
	}

	// *********************************************************************************************
	// partial date
	// *********************************************************************************************

	static public function getPartialDateTestTuples()
	{
		return [
			// input can be a date string
			['2010-06-01', '', '2010-06-01'],
			// ...a DateTime object
			[new DateTime('2010-06-01'), '', '2010-06-01'],
			// ...or a Unix timestamp
			[self::NOW_TS, '', '2010-06-01'],

			// if day or month are not specified, they shall not be output
			['1968-02-29', '', '1968-02-29'],
			['1968-02', '', '1968-02'],
			['1968', '', '1968'],

			// limit output to m[o]nth
			['1968-02-29', 'o', '1968-02'],
			['1968-02', 'o', '1968-02'],
			['1968', 'o', '1968'],
			[new DateTime('1968-02-29'), 'o', '1968-02'],
			[self::NOW_TS, 'o', '2010-06'],

			// limit output to [y]ear
			['1968-02-29', 'y', '1968'],
			['1968-02', 'y', '1968'],
			['1968', 'y', '1968'],
			[new DateTime('1968-02-29'), 'y', '1968'],
			[self::NOW_TS, 'y', '2010'],

			// [y] wins over [o] if both are specified
			['1968-02-29', 'yo', '1968'],

			// use abbreviated [m]onth name
			['1968-02-29', 'm', '29 Feb 1968'],
			['1968-02', 'm', 'Feb 1968'],
			['1968', 'm', '1968'],

			// use full [M]onth name
			['1968-02-29', 'M', '29 February 1968'],
			['1968-02', 'M', 'February 1968'],
			['1968', 'M', '1968'],

			// combine features
			['1968-02-29', 'oM', 'February 1968'],

			// invalid inputs
			['foo-bar', '', ''],
			['22', '', ''],
			[null, '', ''],
			[true, '', ''],

			// dates outside 32-bit Unix timestamp range should be no problem
			['1547-01-10', 'o', '1547-01'],
			['2264-12-24', 'o', '2264-12'],

			// leading zeroes should not be required
			['1968-3-1', '', '1968-03-01'],
		];
	}

	public function testPartialDateFormatDefaultValueCanBeOverridden()
	{
		$result = $this->object->formatPartialDate('invalid', '', 'default');

		$this->assertSame('default', $result);
	}
}
