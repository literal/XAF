<?php
namespace XAF\view\helper;

class DateTimeHelperDe extends DateTimeHelper
{
	static protected $patterns = [
		'date_numeric' => 'd.m.Y',
		'date_with_month_name' => 'j. %\\s Y',

		'year_and_month_numeric' => 'm.Y',
		'year_and_month_name' => '%\\s Y',

		'time' => 'H:i',
		'time_with_seconds' => 'H:i:s',
	];

	static protected $words = [
		'on' => 'am ',
		'at' => ' um ',
		'oclock' => ' Uhr',

		'day_relative' => [-2 => 'vorgestern', -1 => 'gestern', 0 => 'heute', 1 => 'morgen', 2 => 'übermorgen'],

		'weekday_short' => ['Mo.', 'Di.', 'Mi.', 'Do.', 'Fr.', 'Sa.', 'So.'],
		'weekday_long' => ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'],

		'month_short' => [1 => 'Jan.', 'Feb.', 'März', 'Apr.', 'Mai', 'Jun.', 'Jul.', 'Aug.', 'Sep.', 'Okt.', 'Nov.', 'Dez.'],
		'month_long' => [1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],

		'duration_units' => [
			'short' => [
				'h' => ['one' => 'h', 'many' => 'h'],
				'm' => ['one' => 'min', 'many' => 'min'],
				's' => ['one' => 's', 'many' => 's']
			],
			'medium' => [
				'h' => ['one' => 'Std.', 'many' => 'Std.'],
				'm' => ['one' => 'Min.', 'many' => 'Min.'],
				's' => ['one' => 'Sek.', 'many' => 'Sek.']
			],
			'long' => [
				'h' => ['one' => 'Stunde', 'many' => 'Stunden'],
				'm' => ['one' => 'Minute', 'many' => 'Minuten'],
				's' => ['one' => 'Sekunde', 'many' => 'Sekunden']
			]
		],
	];

	// Only a few european time zones have common German abbreviations
	static protected $localTimeZoneAbbreviations = [
		'CET' => 'MEZ',
		'CEST' => 'MESZ',
		'EET' => 'OEZ',
		'EEST' => 'OESZ',
		'WET' => 'WEZ',
		'WEST' => 'WESZ'
	];

	protected function getTimeZoneAbbreviation( \DateTime $time )
	{
		$result = parent::getTimeZoneAbbreviation($time);
		return self::$localTimeZoneAbbreviations[$result] ?? $result;
	}
}
