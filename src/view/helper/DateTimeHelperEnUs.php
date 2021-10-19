<?php
namespace XAF\view\helper;

class DateTimeHelperEnUs extends DateTimeHelper
{
	static protected $patterns = [
		'date_numeric' => 'n/j/Y',
		'date_with_month_name' => '%\\s j, Y',

		'year_and_month_numeric' => 'n/Y',
		'year_and_month_name' => '%\\s Y',

		'time' => 'g:i a',
		'time_with_seconds' => 'g:i:s a',
	];
}
