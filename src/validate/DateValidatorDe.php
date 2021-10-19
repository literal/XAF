<?php
namespace XAF\validate;

class DateValidatorDe extends DateValidator
{
	/** @var string preg pattern to macht the input against, must contain named subpatterns 'y', 'm' and 'd' */
	static protected $datePregPattern = '/^(?P<d>[0-9]{1,2}).\\s*(?P<m>[0-9]{1,2}).\\s*(?P<y>[0-9]{2,4})$/';

	/** @var string human readable representation of the expected pattern for invalid date format error */
	static protected $formatDescription = 'TT.MM.JJJJ';
}
