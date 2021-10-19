<?php
namespace XAF\validate;

use DateTime;
use DateTimeZone;

/**
 * Language independent ISO/DIN/SQL date validator
 *
 * @errorKey invalidDateFormat(expected)
 * @errorKey invalidDate
 */
class DateValidator extends NotEmptyValidator
{
	/** @var string preg pattern to macht the input against, must contain named subpatterns 'y', 'm' and 'd' */
	static protected $datePregPattern = '/^(?P<y>[0-9]{2,4})-(?P<m>[0-9]{2})-(?P<d>[0-9]{2})$/';

	/** @var string human readable representation of the expected pattern for invalid date format error */
	static protected $formatDescription = 'YYYY-MM-DD';

	/** @var DateTimeZone|null */
	protected $timeZone;

	/**
	 * @param mixed $timeZone The time zone identifier (e.g. 'Europe/Berlin') or a DateTimeZone object,
	 *     defaults to the server's time zone (as set in php.ini).
	 *     The DateTime instance resulting from a successful validation will be set to this timezone.
	 */
	function __construct( $timeZone = null )
	{
		$this->timeZone = $this->normaliseTimeZone($timeZone);
	}

	/**
	 * @param DateTimeZone|string|null $value
	 * @return DateTimeZone
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

	public function validate( $value )
	{
		$result = parent::validate($value);
		if( $result->errorKey )
		{
			return $result;
		}

		$value = $result->value;

		if( $value instanceof DateTime )
		{
			$result = $value;
		}
		else
		{
			if( !\preg_match(static::$datePregPattern, $value, $parts) )
			{
				return ValidationResult::createError('invalidDateFormat', ['expected' => static::$formatDescription]);
			}

			$year = \intval($parts['y'], 10);
			if( $year < 1000 )
			{
				$year += 2000;
			}
			$month = \intval($parts['m'], 10);
			$day = \intval($parts['d'], 10);
			if( !\checkdate($month, $day, $year) )
			{
				return ValidationResult::createError('invalidDate');
			}

			$isoDate = \sprintf('%04u-%02u-%02u', $year, $month, $day);

			$result = new DateTime($isoDate, $this->timeZone);
		}

		return ValidationResult::createValid($result);
	}
}
