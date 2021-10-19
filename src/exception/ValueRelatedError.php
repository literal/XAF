<?php
namespace XAF\exception;

/**
 * Base class for Exceptions that refer to a value causing the error
 */
abstract class ValueRelatedError extends ExtendedError
{
	/**
	 * @param string $message
	 * @param mixed $relatedValue
	 * @param mixed $details
	 */
	public function __construct( $message, $relatedValue = null, $details = null )
	{
		parent::__construct(
			$message . (isset($relatedValue) ? ': ' . $this->formatRelatedValueForMessage($relatedValue) : ''),
			['related value' => $relatedValue, 'error details' => $details]
		);
	}

	/**
	 * @param mixed $relatedValue
	 * @param int $maxLength
	 * @return string
	 */
	protected function formatRelatedValueForMessage( $relatedValue, $maxLength = 80 )
	{
		$result = '';
		switch( true )
		{
			case \is_null($relatedValue):
				$result = 'null';
				break;

			case \is_bool($relatedValue):
				$result = $relatedValue ? 'true' : 'false';
				break;

			case \is_scalar($relatedValue):
				$result = \strval($relatedValue);
				break;

			case \is_array($relatedValue):
				$result = 'array[' . \sizeof($relatedValue) . ']';
				break;

			case \is_object($relatedValue):
				$result = 'object:' . \get_class($relatedValue);
				break;
		}

		if( \mb_strlen($result, 'UTF-8') > $maxLength )
		{
			$result = \mb_substr($result, 0, $maxLength, 'UTF-8') . '…';
		}

		return $result;
	}

	/**
	 * @return mixed The offending value
	 */
	public function getValue()
	{
		return $this->debugInfo['related value'] ?? null;
	}
}
