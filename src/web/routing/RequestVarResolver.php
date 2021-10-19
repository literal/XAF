<?php
namespace XAF\web\routing;

use XAF\validate\ValidationService;
use XAF\http\Request;
use XAF\web\exception\RequestFieldError;

/**
 * Helper for the routing process
 * Computes the value of a request var from an expression in the routing table
 */
class RequestVarResolver
{
	/** @var Request */
	protected $request;

	/** @var ValidationService */
	protected $validationService;

	public function __construct( Request $request, ValidationService $validationService )
	{
		$this->request = $request;
		$this->validationService = $validationService;
	}

	/**
	 * Compute request var value
	 * - Either fetch value from HTTP request if expression starts with 'POSTVAL', 'GETVAL', 'REQUESTVAL' or 'COOKIE'
	 *   (optionally followed by field name in parenthesis, otherwise var name is used as field name)
	 * - Or use value literally
	 * - When present, apply part after (last) colon as validation expression
	 *
	 * Expression examples:
	 * - "literal value" -> return as is
	 * - "POSTVAL" -> fetch field $varName from HTTP POST data without validation
	 * - "GETVAL(foo):int" -> fetch field 'foo' from HTTP GET data and transform to integer (throw BadRequest if
	 *       field contains anything but numbers)
	 * - "literal:or(empty,string(2)) -> use literal value, return null when empty, throw BadRequest when length is
	 *       1 or return string if 2 chars or longer
	 *
	 * @param string $varName the request var's name
	 * @param mixed $expression the value or expression for the request var
	 * @return mixed
	 */
	public function resolveVar( $varName, $expression )
	{
		// Non-string values (e.g. boolean, array) are set literally in the routing map and shall not be touched
		if( !\is_string($expression) )
		{
			return $expression;
		}

		$parts = $this->splitExpression($expression);
		$requestFieldKey = $parts['sourceField'] !== null ? $parts['sourceField'] : $varName;
		$value = $parts['source']
			? $this->fetchRequestValue($parts['source'], $requestFieldKey)
			: $parts['value'];

		return $parts['rule'] !== null ? $this->applyValidationRule($requestFieldKey, $value, $parts['rule']) : $value;
	}

	/**
	 * @param string $expression
	 * @return array {
	 *    source: <string|null>, // Optional, key of request collection
	 *    sourceField: <string|null>, // Optional, name of request field if not var name
	 *    value: <string>, // Literal value when not from request
	 *    rule: <string|null> // Optional, validation expression
	 * }
	 */
	protected function splitExpression( $expression )
	{
		\preg_match(
			'/^'
			. '(?:'
				// either request source with optional source field name in parenthesis:
				. '(POSTVAL|GETVAL|REQUESTVAL|COOKIE)(?(1)\\(([^)]+)\\))?'
			. '|'
				// ... or literal value:
				. '(.*?)'
			. ')'
			// validation expression after last colon:
			. '(?::([^:]+))?'
			. '$/',
			$expression,
			$matches
		);
		return [
            'source' => isset($matches[1]) && $matches[1] !== '' ? $matches[1] : null,
            'sourceField' => isset($matches[2]) && $matches[2] !== '' ? $matches[2] : null,
            'value' => $matches[3] ?? '',
            'rule' => isset($matches[4]) && $matches[4] !== '' ? $matches[4] : null,
		];
	}

	/**
	 * @param string $source
	 * @param string $fieldName
	 * @return string|null
	 */
	protected function fetchRequestValue( $source, $fieldName )
	{
		switch( $source )
		{
			case 'POSTVAL':
				return $this->request->getPostField($fieldName);

			case 'GETVAL':
				return $this->request->getQueryParam($fieldName);

			case 'REQUESTVAL':
				$value = $this->request->getPostField($fieldName);
				if( $value !== null )
				{
					return $value;
				}
				return $this->request->getQueryParam($fieldName);

			case 'COOKIE':
				return $this->request->getCookie($fieldName);
		}
		return null;
	}

	/**
	 * @param string $requestFieldKey only used for error message
	 * @param string $value
	 * @param string $filterExpression
	 * @return mixed
	 */
	protected function applyValidationRule( $requestFieldKey, $value, $filterExpression )
	{
		$validationResult = $this->validationService->validate($value, $filterExpression);
		if( $validationResult->errorKey !== null )
		{
			throw new RequestFieldError($requestFieldKey, $value === '' ? null : $value, 'rule: ' . $filterExpression);
		}

		return $validationResult->value;
	}
}

