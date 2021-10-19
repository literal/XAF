<?php
namespace XAF\helper;

/**
 * Static helper for expanding messages with parameter placeholders
 */
class MessageRenderer
{
	private function __construct()
	{
	}

	/**
	 * The pattern may either be a callable (in which case it is called with the params) or a string containing
	 * "%name%" placeholders for inserting the params.
	 *
	 * Escape sequence for a literal percent sign is "%%".
	 *
	 * @param string|callable $pattern
	 * @param array $params
	 * @return string|null
	 */
	static public function render( $pattern, array $params )
	{
		if( \is_string($pattern) )
		{
			return \preg_replace_callback(
				'/%([A-Za-z0-9_]*)%/',
				function( $matches ) use( $params ) {
					$paramKey = $matches[1];
					return $paramKey === '' ? '%' : (isset($params[$paramKey]) ? $params[$paramKey] : '?');
				},
				$pattern
			);
		}

		if( \is_callable($pattern) )
		{
			return \call_user_func($pattern, $params);
		}

		return null;
	}
}
