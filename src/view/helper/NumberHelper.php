<?php
namespace XAF\view\helper;

/**
 * For an informal list of intl number formats see: http://www.codeproject.com/KB/locale/NumberFormats.aspx
 */
class NumberHelper
{
	static protected $fileSizeUnits = [
		['unit' => 'Byte', 'digits' => 0],
		['unit' => 'kB', 'digits' => 0],
		['unit' => 'MB', 'digits' => 1],
		['unit' => 'GB', 'digits' => 1],
		['unit' => 'TB', 'digits' => 2],
		['unit' => 'PB', 'digits' => 2]
	];

	static protected $numberSeparators = [
		'decimal' => '.',
		'thousands' => ','
	];

	static protected $bitrateUnits = ['bit/s', 'kbit/s', 'Mbit/s', 'Gbit/s', 'Tbit/s', 'Pbit/s'];

	/**
	 * format a number for output in localized format
	 *
	 * @param mixed $value
	 * @param int $precision Number of fractional digits
	 * @param bool $separateThousands Whether to group 1000s
	 * @param string $default Value to return if given number empty or not numeric
	 * @return string
	 */
	public function formatNumber( $value, $precision = 0, $separateThousands = true, $default = '' )
	{
		return \is_numeric($value)
			? \number_format(
				$value,
				$precision,
				static::$numberSeparators['decimal'],
				$separateThousands ? static::$numberSeparators['thousands'] : ''
			  )
			: $default;
	}

	/**
	 * Format a number of bytes as a file size with unit - unit is automatically determined from the
	 * magnitude of the number of bytes.
	 *
	 * @param mixed $size Number of bytes
	 * @param string $default Value to return if number of bytes empty or not numeric
	 * @return string
	 */
	public function formatFilesize( $size, $default = '' )
	{
		if( !\is_numeric($size) )
		{
			return $default;
		}

		$unitCount = \sizeof(static::$fileSizeUnits);
		$unitIndex = 0;
		while( $size > 1000 && $unitIndex < $unitCount - 1 )
		{
			$size /= 1000;
			$unitIndex++;
		}
		return $this->formatNumber($size, static::$fileSizeUnits[$unitIndex]['digits'])
			. ' ' . static::$fileSizeUnits[$unitIndex]['unit'];
	}

	/**
	 * @param int $value Bits per second
	 * @param string $default Value to return if bitrate empty or not numeric
	 * @return string
	 */
	public function formatBitrate( $value, $default = '' )
	{
		if( !\is_numeric($value) )
		{
			return $default;
		}

		$units = static::$bitrateUnits;
		$unitIndex = 0;
		while( $value > 1000 && $unitIndex < (\sizeof($units) - 1) )
		{
			$value /= 1000;
			$unitIndex++;
		}
		return $this->formatNumber($value, 0) . ' ' . $units[$unitIndex];
	}

}
