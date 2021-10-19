<?php
namespace XAF\view\helper;

use PHPUnit\Framework\TestCase;

use DateTime;

abstract class DateTimeHelperTestBase extends TestCase
{
	const TIMEZONE = 'Europe/Berlin';
	// reference for relative date/time tests
	const NOW = '2010-06-01 12:00:00 Europe/Berlin';
	const NOW_TS = 1275386400;

	/** @var DateTime */
	static protected $now;

	/** @var DateTimeHelper */
	protected $object;

	static public function setUpBeforeClass(): void
	{
		self::$now = new DateTime(self::NOW);
	}

	static public function getDateFormatTestTuples()
	{
		return [];
	}

	/**
	 * @dataProvider getDateFormatTestTuples
	 */
	public function testDateFormat( $input, $options, $expectedResult )
	{
		$actualResult = $this->object->formatDate($input, $options, '', static::$now);

		$this->assertSame($expectedResult, $actualResult);
	}

	static public function getTimeFormatTestTuples()
	{
		return [];
	}

	/**
	 * @dataProvider getTimeFormatTestTuples
	 */
	public function testTimeFormat( $input, $options, $expectedResult )
	{
		$actualResult = $this->object->formatTime($input, $options);

		$this->assertSame($expectedResult, $actualResult);
	}

	static public function getDateAndTimeFormatTestTuples()
	{
		return [];
	}

	/**
	 * @dataProvider getDateAndTimeFormatTestTuples
	 */
	public function testDateAndTimeFormat( $input, $options, $expectedResult )
	{
		$actualResult = $this->object->formatDateAndTime($input, $options);

		$this->assertSame($expectedResult, $actualResult);
	}

	static public function getDurationFormatTestTuples()
	{
		return [];
	}

	/**
	 * @dataProvider getDurationFormatTestTuples
	 */
	public function testDurationFormat( $input, $options, $expectedResult )
	{
		$actualResult = $this->object->formatDuration($input, $options);

		$this->assertEquals($expectedResult, $actualResult);
	}

	static public function getPartialDateTestTuples()
	{
		return [];
	}

	/**
	 * @dataProvider getPartialDateTestTuples
	 */
	public function testPartialDate( $input, $options, $expectedResult )
	{
		$actualResult = $this->object->formatPartialDate($input, $options);

		$this->assertEquals($expectedResult, $actualResult);
	}
}
