<?php
namespace XAF\validate;

use XAF\di\DiContainer;

class DefaultValidationService implements ValidationService
{
	/** @var DiContainer */
	protected $container;

	/** @var string */
	protected $validatorKeyPostfix = '';

	/**
	 * @param DiContainer $container
	 * @param string|null $objectQualifier Qualifier to add to the object keys of the validator requested from the DI
	 *     container - usually specifies the language, e. g. 'de.de'
	 */
	public function __construct( DiContainer $container, $objectQualifier = null )
	{
		$this->container = $container;
		$this->validatorKeyPostfix = $objectQualifier ? '.' . $objectQualifier : '';
	}

	/**
	 * @param mixed $value
	 * @param string $expression
	 * @return ValidationResult
	 */
	public function validate( $value, $expression )
	{
		$terms = \explode('|', $expression);
		foreach( $terms as $term )
		{
			$result = $this->callValidator($term, $value);
			if( $result->errorKey !== null )
			{
				return $result;
			}
			$value = $result->value;
		}
		return $result;
	}

	/**
	 * @param string $validationExpression
	 * @param mixed $value
	 * @return ValidationResult
	 */
	private function callValidator( $validationExpression, $value )
	{
		list($validatorKey, $arguments) = $this->parseValidationExpression($validationExpression);

		$validator = $this->container->getLocal($validatorKey . $this->validatorKeyPostfix);

		\array_unshift($arguments, $value);
		return \call_user_func_array([$validator, 'validate'], $arguments);
	}

	/**
	 * @param string $expression
	 * @return array [<validator key>, [<argument>, ...]]
	 */
	public function parseValidationExpression( $expression )
	{
		$firstParenPos = \strpos($expression, '(');
		if( $firstParenPos === false )
		{
			return [\trim($expression), []];
		}

		$lastParenPos = \strrpos($expression, ')');
		if( $lastParenPos === false )
		{
			$lastParenPos = \strlen($expression);
		}

		$validatorKey = \trim(\substr($expression, 0, $firstParenPos));
		$argumentString = \substr($expression, $firstParenPos + 1, $lastParenPos - $firstParenPos - 1);
		return [$validatorKey, $this->splitArguments($argumentString)];
	}

	/**
	 * @param string $argumentString
	 * @return array
	 */
	private function splitArguments( $argumentString )
	{
		$nestingLevel = 0;
		$len = \strlen($argumentString);
		$result = [];
		$currentArg = '';
		for( $i = 0; $i < $len; $i++ )
		{
			$c = $argumentString[$i];

			if( $nestingLevel == 0 && $c == ',' )
			{
				$currentArg = \trim($currentArg);
				if( $currentArg !== '' )
				{
					$result[] = $currentArg;
					$currentArg = '';
				}
			}
			else
			{
				if( $c == '(' )
				{
					$nestingLevel++;
				}
				else if( $c == ')' )
				{
					$nestingLevel--;
				}
				$currentArg .= $c;
			}
		}
		$currentArg = \trim($currentArg);
		if( $currentArg !== '' )
		{
			$result[] = $currentArg;
		}

		return $result;
	}
}
