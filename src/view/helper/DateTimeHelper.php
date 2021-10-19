<?php
namespace XAF\view\helper;

use DateTime;
use DateTimeZone;
use Exception;

/**
 * International date and time computation an formatting using ISO date and time formats and English words
 */
class DateTimeHelper
{
	static protected $patterns = [
		'date_numeric' => 'Y-m-d',
		'date_with_month_name' => 'j %\\s Y',

		'year_and_month_numeric' => 'Y-m',
		'year_and_month_name' => '%\\s Y',

		'time' => 'H:i',
		'time_with_seconds' => 'H:i:s',
	];

	static protected $words = [
		'on' => 'on ',
		'at' => ' at ',
		'oclock' => '', // putting "o'clock" after numeric time values is not common, this field is here for other languages

		'day_relative' => [-1 => 'yesterday', 0 => 'today', 1 => 'tomorrow'],

		'weekday_short' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
		'weekday_long' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],

		'month_short' => [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
		'month_long' => [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],

		'duration_units' => [
			'short' => [
				'h' => ['one' => 'h', 'many' => 'h'],
				'm' => ['one' => 'min', 'many' => 'min'],
				's' => ['one' => 's', 'many' => 's']
			],
			'medium' => [
				'h' => ['one' => 'hr', 'many' => 'hrs'],
				'm' => ['one' => 'min', 'many' => 'min'],
				's' => ['one' => 'sec', 'many' => 'sec']
			],
			'long' => [
				'h' => ['one' => 'hour', 'many' => 'hours'],
				'm' => ['one' => 'minute', 'many' => 'minutes'],
				's' => ['one' => 'second', 'many' => 'seconds']
			]
		],
	];

	/** @var DateTime */
	protected $now;

	/** @var DateTimeZone */
	protected $timeZone;

	/**
	 * @param mixed $timeZone The time zone identifier (e.g. 'Europe/Berlin') or a DateTimeZone object, defaults
	 *     to the server's time zone (as set in php.ini)
	 *
	 * Note that the time zone does not only affect time but also dates: The same absolute point in time may
	 * be on one day in the UK while it is already the next day in Russia.
	 */
	function __construct( $timeZone = null )
	{
		$this->now = new DateTime();
		$this->timeZone = $this->normaliseTimeZone($timeZone) ?: $this->now->getTimezone();
	}

	/**
	 * @param string|DateTimeZone $value
	 * @return DateTimeZone|null
	 */
	protected function normaliseTimeZone( $value )
	{
		if( $value instanceof DateTimeZone )
		{
			return $value;
		}
		if( \is_string($value) )
		{
			return new DateTimeZone($value);
		}
		return null;
	}

	/**
	 * Option characters - specify in a single string in any order, e.g. "cfoaM":
	 *
	 * - a: use "[a]t" (or local equivalent) between date and time, e.g. "yesterday at 13:33"
	 *
	 * In addition, all option characters for formatDate() and formatTime() can be used
	 *
	 * @param mixed $value A Unix timestamp, a date string recognised by DateTime::__construct() or a DateTime instance
	 * @param string $optionString
	 * @param string $default value to return if $date contains no valid date
	 * @param mixed $now base for relative formats (e.g. "yesterday"), default is null for current time
	 * @return string
	 */
	public function formatDateAndTime( $value, $optionString = '', $default = '', $now = null )
	{
		$dateTime = $this->normaliseDateTime($value);
		if( $dateTime === null )
		{
			return $default;
		}

		$options = $this->buildOptionsFromString('auromMwWscz', $optionString);

		return
			$this->buildDateString($dateTime, $options, $now) .
			($options['a'] ? static::$words['at'] : ' ') .
			$this->buildTimeString($dateTime, $options);
	}

	/**
	 * Option characters - specify in a single string in any order, e.g. "rWMo":
	 *
	 * - r: return [r]elative date if applicable, e.g. "yesterday"
	 * - u: [u]pper-case first character if applicable, e.g. "Today" or "On Thursday, 14 July 2011"
	 * - o: prefix non-relative dates with "[o]n" (or local equivalent), e.g. "on 14/07/2011" but not "on tomorrow"
	 * - m: use abbreviated [m]onth names instead of numbers, e.g. "14 Jul 2011"
	 * - M: use full [M]onth names, e.g. "14 July 2011"
	 * - w: use abbreviated [w]eekday name, e.g. "Mon 14/07/2011"
	 * - W: use full [w]eekday name, e.g. "Monday 14/07/2011"
	 *
	 * @param mixed $value A Unix timestamp, a date string recognised by DateTime::__construct() or a DateTime instance
	 * @param string $optionString
	 * @param string $default value to return if $date contains no valid date
	 * @param mixed $now base for relative formats (e.g. "yesterday"), default is null for current time
	 * @return string
	 */
	public function formatDate( $value, $optionString = '', $default = '', $now = null )
	{
		$date = $this->normaliseDateTime($value);
		if( $date === null )
		{
			return $default;
		}

		$options = $this->buildOptionsFromString('ruomMwW', $optionString);
		return $this->buildDateString($date, $options, $now);
	}

	/**
	 * @param DateTime $date
	 * @param array $options
	 * @param mixed $now reference for relative results like "yesterday"
	 * @return string
	 */
	protected function buildDateString( DateTime $date, array $options, $now = null )
	{
		$result = null;
		if( $options['r'] )
		{
			$result = $this->buildRelativeDateString($date, $now);
		}
		if( !$result )
		{
			$result = $this->buildAbsoluteDateString($date, $options);
		}

		return $options['u'] ? $this->capitaliseFirstChar($result) : $result;
	}

	/**
	 * @param DateTime $date
	 * @param mixed $now
	 * @return string|null null if no relative expression is available
	 */
	protected function buildRelativeDateString( DateTime $date, $now = null )
	{
		$now = $this->normaliseDateTime($now) ?: $this->now;
		$dateDifference = $this->getDateDifferenceInDays($date, $now);
		return static::$words['day_relative'][$dateDifference] ?? null;
	}

	/**
	 * Compute the difference between two dates in days
	 *
	 * Ignores time, i.e. the difference between '2011-07-14 00:00' and '2011-07-13 23:59' is one day,
	 * even though the the real difference between these two points in time is just one minute.
	 *
	 * @param DateTime $date
	 * @param DateTime $reference
	 * @return int the difference in days, if $date is before $reference, the result is a negative number
	 */
	protected function getDateDifferenceInDays( DateTime $date, DateTime $reference )
	{
		// The dates are extracted and put into UTC context to avoid daylight saving time issues (e.g. interval
		// becomes n-1 days + 23 hours when reference is DST and date is not)
		$dateNoonUtc = new DateTime($date->format('Y-m-d') . 'T12:00UTC');
		$referenceNoonUtc = new DateTime($reference->format('Y-m-d') . 'T12:00UTC');
		$interval = $referenceNoonUtc->diff($dateNoonUtc);
		return \intval($interval->format('%r%a'), 10);
	}

	/**
	 * @param DateTime $date
	 * @param array $options
	 * @return string
	 */
	protected function buildAbsoluteDateString( DateTime $date, array $options )
	{
		$result = $options['o'] ? static::$words['on'] : '';

		if( $options['W'] || $options['w'] )
		{
			$result .= $this->getWeekdayName($date, $options['W']) . ', ';
		}

		if( $options['M'] || $options['m'] )
		{
			$monthName = $this->getMonthName($date, $options['M']);
			$result .= \sprintf($date->format(static::$patterns['date_with_month_name']), $monthName);
		}
		else
		{
			$result .= $date->format(static::$patterns['date_numeric']);
		}

		return $result;
	}

	/**
	 * @param DateTime $date
	 * @param bool $long
	 * @return string
	 */
	protected function getWeekdayName( DateTime $date, $long )
	{
		$weekdayNames = static::$words[$long ? 'weekday_long' : 'weekday_short'];
		$weekdayIndex = \intval($date->format('N')) - 1;
		return $weekdayNames[$weekdayIndex];
	}

	/**
	 * @param DateTime $date
	 * @param bool $long
	 * @return string
	 */
	protected function getMonthName( DateTime $date, $long )
	{
		$monthNames = static::$words[$long ? 'month_long' : 'month_short'];
		$monthNumber = \intval($date->format('n'));
		return $monthNames[$monthNumber];
	}

	/**
	 * @param string $string
	 * @return string
	 */
	protected function capitaliseFirstChar( $string )
	{
		return \strtoupper($string[0]) . \substr($string, 1);
	}

	/**
	 * Option characters - specify in a single string in any order, e.g. "cs":
	 *
	 * - s: include [s]econds, e.g. "13:27:49" instead of "13:27"
	 * - c: put "o'[c]lock" (or local equivalent like German "Uhr") after time unless 12-hour-AM/PM-format
	 * - z: add time zone abbreviation, e.g. 'CET' or 'PDT'
	 *
	 * @param mixed $value A Unix timestamp, a date string recognised by DateTime::__construct() or a DateTime instance
	 * @param string $optionString
	 * @param string $default value to return if $value contains no valid date
	 * @return string
	 */
	public function formatTime( $value, $optionString = '', $default = '' )
	{
		$time = $this->normaliseDateTime($value);
		if( $time === null )
		{
			return $default;
		}

		$options = $this->buildOptionsFromString('scz', $optionString);

		return $this->buildTimeString($time, $options);
	}

	/**
	 * @param DateTime $time
	 * @param array $options
	 * @return string
	 */
	protected function buildTimeString( DateTime $time, array $options )
	{
		$pattern = static::$patterns[$options['s'] ? 'time_with_seconds' : 'time'];
		return $time->format($pattern)
			. ($options['c'] ? static::$words['oclock'] : '')
			. ($options['z'] ? ' ' . $this->getTimeZoneAbbreviation($time) : '');
	}

	protected function getTimeZoneAbbreviation( DateTime $time )
	{
		return $time->format('T');
	}

	/**
	 * Convert any supported value into a DateTime object referring to the same time zone
	 *
	 * @param mixed $value A Unix timestamp, a date string recognised by DateTime::_construct() or a DateTime instance
	 * @return DateTime|null
	 */
	protected function normaliseDateTime( $value )
	{
		$dateTime = null;
		try
		{
			if( $value instanceof DateTime )
			{
				$dateTime = clone $value;
			}
			else if( \is_int($value) )
			{
				$dateTime = new DateTime('@' . $value);
			}
			else if( \is_string($value) )
			{
				$dateTime = new DateTime($value);
			}
		}
		catch( Exception $e ) {}

		if( $dateTime )
		{
			$dateTime->setTimezone($this->timeZone);
			return $dateTime;
		}
		return null;
	}

	/**
	 * Build a human readable duration string from a number of seconds
	 *
	 * Option characters - specify in a single string in any order, e.g. "mef":
	 *
	 * - H: use [H]ours as leftmost unit (i.e. always output hours even when 0)
	 * - M: use [M]inutes as leftmost unit (i.e. do not output hours even when duration >= 60 min and do
	 *      not switch to seconds when duration < 60 sec)
	 * - S: use [S]econds as leftmost unit (i.e. do not output minutes or hours even when duration >= 60 sec)
	 *
	 * - h: use [h]ours as rightmost unit (round to full hours)
	 * - m: use [m]inutes as rightmost unit (round to full minutes)
	 * - s: use [s]econds as rightmost unit
	 *
	 * - n: [n]umeric only (no units, usually used together with 'H'/'M'/'S' and 'h'/'m'/'s' for consistent output units)
	 * - e: [e]xtended format, e.g. "3 h 22 min" instead of "3:22 h"
	 * - a: add [a]bbreviated unit names instead of unit signs
	 * - f: add [f]ull unit names instead of unit signs
	 *
	 * - i: [i]SO 8601 PT(HMS) - renders all other options meaningless!
	 *
	 * If units/precision are not specified, the appropriate units/precision will be selected automatically depending
	 * on the magnitude of $totalSeconds.
	 *
	 * @param int $totalSeconds
	 * @param string $optionString
	 * @param string $default value to return if $date contains no valid date
	 * @return string
	 */
	public function formatDuration( $totalSeconds, $optionString = '', $default = '' )
	{
		if( !\is_numeric($totalSeconds) )
		{
			return $default;
		}

		$options = $this->buildOptionsFromString('HMShmsnefai', $optionString);

		if( $options['i'] )
		{
			return $this->formatIsoDuration($totalSeconds);
		}

		$values = $this->splitDurationIntoHms($totalSeconds, $options);

		if( $options['n'] )
		{
			return $this->formatNumericDuration($values);
		}

		$unitFormat = $options['f'] ? 'long' : ($options['a'] ? 'medium' : 'short');

		return $options['e']
			? $this->formatExtendedDuration($values, $unitFormat)
			: $this->formatNumericDurationWithUnit($values, $unitFormat);
	}

	/**
	 * @param int $seconds
	 * @return string
	 */
	protected function formatIsoDuration( $seconds )
	{
		$h = \floor($seconds / 3600);
		$m = \floor(($seconds % 3600) / 60);
		$s = $seconds % 60;
		return \sprintf('PT%uH%uM%uS', $h, $m, $s);
	}

	/**
	 * @param int $totalSeconds
	 * @param array $options
	 * @return array Values to display, [h: <int|null>, m: <int|null>, s: <int|null>]
	 */
	protected function splitDurationIntoHms( $totalSeconds, array $options )
	{
		$result = ['h' => null, 'm' => null, 's' => null];

		$useHours = ($options['H'] || $options['h'] || $totalSeconds >= 3600)
			&& !($options['M'] || $options['S']);
		$useMinutes = ($options['M'] || $options['m'] || $useHours || $totalSeconds >= 60)
			&& !($options['h'] || $options['S']);
		$useSeconds = ($options['S'] || $options['s'] || !$useHours)
			&& !($options['h'] || $options['m']);

		if( $useSeconds )
		{
			$result['s'] = $useMinutes ? $totalSeconds % 60 : $totalSeconds;
		}
		else
		{
			$totalSeconds = 60 * \round($totalSeconds / 60);
		}

		if( $useMinutes )
		{
			$minutes = ($useHours ? $totalSeconds % 3600 : $totalSeconds) / 60;
			$result['m'] = $useSeconds ? \floor($minutes) : \round($minutes);
		}

		if( $useHours )
		{
			$hours = $totalSeconds / 3600;
			$result['h'] = $useMinutes ? \floor($hours) : \round($hours);
		}

		return $result;
	}

	/**
	 * @param array $values [h: <int|null>, m: <int|null>, s: <int|null>]
	 * @param string $unitFormat 'short', 'medium' or 'long'
	 * @return string
	 */
	protected function formatNumericDurationWithUnit( array $values, $unitFormat )
	{
		$majorValue = $values['h'] ?? ($values['m'] ?? $values['s']);
		$majorUnitKey = (isset($values['h']) ? 'h' : (isset($values['m']) ? 'm' : 's'));

		return $this->formatNumericDuration($values)
			. ' ' . static::$words['duration_units'][$unitFormat][$majorUnitKey][$majorValue == 1 ? 'one' : 'many'];
	}

	/**
	 * @param array $values [h: <int|null>, m: <int|null>, s: <int|null>]
	 * @return string
	 */
	protected function formatNumericDuration( array $values )
	{
		$pattern = '';
		$outValues = [];
		$firstValue = true;
		foreach( ['h', 'm', 's'] as $key )
		{
			if( isset($values[$key]) )
			{
				$pattern .= $firstValue ? '%u' : ':%02u';
				$outValues[] = $values[$key];
				$firstValue = false;
			}
		}

		return \vsprintf($pattern, $outValues);
	}

	/**
	 * @param array $values [h: <int|null>, m: <int|null>, s: <int|null>]
	 * @param string $unitFormat 'short', 'medium' or 'long'
	 * @return string
	 */
	protected function formatExtendedDuration( array $values, $unitFormat )
	{
		$resultParts = [];
		foreach( ['h', 'm', 's'] as $key )
		{
			if( isset($values[$key]) )
			{
				$resultParts[] = $values[$key] . ' '
					. static::$words['duration_units'][$unitFormat][$key][$values[$key] == 1 ? 'one' : 'many'];
			}
		}
		return \implode(' ', $resultParts);
	}

	/**
	 * Calculate the age of a date/time value in the given unit of time
	 *
	 * @param mixed $value A Unix timestamp, a date string recognised by DateTime::__construct() or a DateTime instance
	 * @param string $optionString
	 *     's': in seconds (default)
	 *     'm': in minutes
	 *     'h': in hours
	 *     'd': in days
	 * @param mixed|null $now  A Unix timestamp, a date string recognised by DateTime::__construct() or
	 *     a DateTime instance representing the "now" time the age refers to (defaults to current time if null)
	 * @return float|null Null if $value contains no valid date/time
	 */
	public function computeAge( $value, $optionString = 's', $now = null )
	{
		$value = $this->normaliseDateTime($value);
		if( !$value )
		{
			return null;
		}

		$now = $now ? $this->normaliseDateTime($now) : new DateTime();
		$result = $now->getTimestamp() - $value->getTimestamp();

		$options = $this->buildOptionsFromString('smhd', $optionString);

		if( $options['d'] )
		{
			$result /= 3600 * 24;
		}
		else if( $options['h'] )
		{
			$result /= 3600;
		}
		else if( $options['m'] )
		{
			$result /= 60;
		}

		return $result;
	}

	/**
	 * Format just a year, a year and a month or a full date.
	 *
	 * Optionally, the output can be limited to be just the year or a month and year, even if the input
	 * is more specific.
	 *
	 * Option characters - specify in a single string in any order, e.g. "rWMo":
	 *
	 * - y: return only the [y]ear regardless of how specific the input value is, e.g. "1969"
	 * - o: return only the year and m[o]nth regardless of how specific the input value is, e.g. "1969-04"
	 * - m: use abbreviated [m]onth names instead of numbers, if month is to be shown, e.g. "Jul 2011"
	 * - M: use full [M]onth names, if month is to be shown, e.g. "July 2011"
	 *
	 * @param mixed $value A partial or full date string in ISO format, i.e. 'YYYY', 'YYYY-MM' or 'YYYY-MM-DD',
	 *      a Unix timestamp or a DateTime instance
	 * @param string $optionString
	 * @param string $default value to return if $date contains no valid date
	 * @return string
	 */
	public function formatPartialDate( $value, $optionString = '', $default = '' )
	{
		$options = $this->buildOptionsFromString('yomM', $optionString);

		list($value, $availablePartCount) = $this->parsePartialDateValue($value);

		$date = $this->normaliseDateTime($value);
		if( !$date )
		{
			return $default;
		}

		$desiredPartCount = $options['y'] ? 1 : ($options['o'] ? 2 : 3);
		$effectivePartCount = \min($availablePartCount, $desiredPartCount);

		return $this->buildPartialDateString($date, $effectivePartCount, $options);
	}

	/**
	 *
	 * @param mixed $value
	 * @return array [<value>, <part count>]
	 */
	protected function parsePartialDateValue( $value )
	{
		if( \is_string($value) && \preg_match('/^(\\d{4})(?:-(\\d{1,2}))?(?:-(\\d{1,2}))?$/', $value, $matches) )
		{
			$availablePartCount = \sizeof($matches) - 1;
			$value = \sprintf(
				'%04u-%02u-%02u',
				$matches[1],
				$availablePartCount > 1 ? $matches[2] : 1,
				$availablePartCount > 2 ? $matches[3] : 1
			);
		}
		else
		{
			$availablePartCount = 3;
		}

		return [$value, $availablePartCount];
	}

	/**
	 * @param DateTime $date
	 * @param int $partCount The number of date items (yer, month, day, in this order) to use
	 * @param array $options
	 * @return string
	 */
	protected function buildPartialDateString( DateTime $date, $partCount, array $options )
	{
		if( $partCount < 2 )
		{
			return $date->format('Y');
		}
		if( $partCount < 3 )
		{
			return $this->buildYearAndMonthString($date, $options);
		}

		// buildAbsoluteDateString() will check weekday options which are not supported in this context
		$options['w'] = false;
		$options['W'] = false;
		return $this->buildAbsoluteDateString($date, $options);
	}

	/**
	 * @param DateTime $date
	 * @param array $options
	 * @return string
	 */
	protected function buildYearAndMonthString( DateTime $date, array $options )
	{
		if( $options['m'] || $options['M'] )
		{
			$monthName = $this->getMonthName($date, $options['M']);
			return \sprintf($date->format(static::$patterns['year_and_month_name']), $monthName);
		}

		return $date->format(static::$patterns['year_and_month_numeric']);
	}

	/**
	 * Create a hash of boolean values indexed by single characters
	 *
	 * @param string $allowedChars all elements to create and initialise with false
	 * @param string $optionString elements to set to true
	 * @return array
	 */
	protected function buildOptionsFromString( $allowedChars, $optionString )
	{
		$options = [];
		$this->setHashElementsFromChars($options, $allowedChars, false);
		$this->setHashElementsFromChars($options, $optionString, true);
		return $options;
	}

	/**
	 * @param array $hash Hash to create/set the elements in
	 * @param string $chars string of chars used as element keys
	 * @param mixed $value value assigned to each element
	 */
	protected function setHashElementsFromChars( array &$hash, $chars, $value )
	{
		$charCount = \strlen($chars);
		for( $i = 0; $i < $charCount; $i++ )
		{
			$char = $chars[$i];
			$hash[$char] = $value;
		}
	}
}
