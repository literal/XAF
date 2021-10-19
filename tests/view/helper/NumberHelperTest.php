<?php
namespace XAF\view\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\view\helper\NumberHelper
 */
class NumberHelperTest extends TestCase
{
	/** @var NumberHelper */
	private $object;

	protected function setUp(): void
	{
		$this->object = new NumberHelper();
	}

	public static function getNumberTestTuples()
	{
		return [
			// different input types
			[126, 0, true, '126'],
			['126', 0, true, '126'],
			[126.33, 0, true, '126'],
			['126.33', 0, true, '126'],

			// fractional digits and rounding
			[71, 2, true, '71.00'],
			[71.5, 2, true, '71.50'],
			[71.555, 2, true, '71.56'],

			// thousands separator
			[1234567, 0, false, '1234567'],
			[1234567, 0, true, '1,234,567'],

			// invalid input
			['ABC', 0, true, ''],
			[true, 0, true, ''],
			[null, 0, true, ''],
		];
	}

	/**
     * @dataProvider getNumberTestTuples
     */
	public function testNumber( $value, $precision, $seperateThousands, $expectedResult )
	{
		$actualResult = $this->object->formatNumber($value, $precision, $seperateThousands);
		$this->assertEquals($expectedResult, $actualResult);
	}

	public function testNumberDefaultValueCanBeOverridden()
	{
		$result = $this->object->formatNumber('invalid', 0, true, 'default');
		$this->assertEquals('default', $result);
	}

	public static function getFilesizeTestTuples()
	{
		return [
			[126, '126 Byte'],
			[126000, '126 kB'],
			[1260000, '1.3 MB'],
			[1260000000, '1.3 GB'],
			[1260000000000, '1.26 TB'],
			[1260000000000000, '1.26 PB'],
			[1260000000000000000, '1,260.00 PB'],

			['ABC', '']
		];
	}

	/**
     * @dataProvider getFilesizeTestTuples
     */
	public function testFilesize( $bytes, $expectedResult )
	{
		$result = $this->object->formatFilesize($bytes);
		$this->assertEquals($expectedResult, $result);
	}

	public function testFilesizeDefaultValueCanBeOverridden()
	{
		$result = $this->object->formatFilesize('invalid', 'default');
		$this->assertEquals('default', $result);
	}

	public static function getBitrateTestTuples()
	{
		return [
			[126, '126 bit/s'],
			[126000, '126 kbit/s'],
			[126000000, '126 Mbit/s'],
			[126000000000, '126 Gbit/s'],
			[126000000000000, '126 Tbit/s'],
			[126000000000000000, '126 Pbit/s'],
			[126000000000000000000, '126,000 Pbit/s'],
			['ABC', '']
		];
	}

	/**
     * @dataProvider getBitrateTestTuples
     */
	public function testBitrate( $bitrate, $expectedResult )
	{
		$result = $this->object->formatBitrate($bitrate);
		$this->assertEquals($expectedResult, $result);
	}

	public function testBitrateDefaultValueCanBeOverridden()
	{
		$result = $this->object->formatBitrate('invalid', 'default');
		$this->assertEquals('default', $result);
	}
}
