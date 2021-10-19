<?php
namespace XAF\view\helper;

class DateTimeHelperEnGb extends DateTimeHelper
{
	static protected $patterns = [
		'date_numeric' => 'd/m/Y',
		'date_with_month_name' => 'j %\\s Y',

		'year_and_month_numeric' => 'm/Y',
		'year_and_month_name' => '%\\s Y',

		'time' => 'H:i',
		'time_with_seconds' => 'H:i:s',
	];
}
