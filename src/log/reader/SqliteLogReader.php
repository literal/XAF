<?php
namespace XAF\log\reader;

use XAF\db\SqlEscaper;
use DateTime;
use DateTimeZone;

class SqliteLogReader extends SqlLogReader
{
	protected function buildFieldSelectTerm( $valueExpression, $alias )
	{
		return $valueExpression . ($valueExpression != $alias ? ' AS ' . $alias : '');
	}

	protected function buildSearchPatternValue( $searchPhrase )
	{
		return '%' . SqlEscaper::likeEscape($searchPhrase) . '%';
	}

	protected function buildSqlSearchTerm( $valueExpression, $placeholderName )
	{
		return  $valueExpression . ' LIKE :' . $placeholderName . ' ESCAPE \'\\\'';
	}

	protected function dateTimeToDbValue( DateTime $dt )
	{
		return $dt->getTimestamp();
	}

	protected function dbToDateTime( $dbValue )
	{
		$result = new DateTime('@' . $dbValue);
		$result->setTimezone(new DateTimeZone(\date_default_timezone_get()));
		return $result;
	}
}
