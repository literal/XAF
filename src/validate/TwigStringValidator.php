<?php
namespace XAF\validate;

use Twig\Environment;
use Twig\Error\SyntaxError as TwigSyntaxError;

/**
 * @errorKey invalidTwigSyntax(message)
 */
class TwigStringValidator extends NotEmptyValidator
{
	/** @var Environment */
	private $twigEnv;

	/**
	 * @param Environment $twigEnv
	 */
	public function __construct( Environment $twigEnv )
	{
		$this->twigEnv = $twigEnv;
	}

	public function validate( $value )
	{
		$result = parent::validate($value);
		if( $result->errorKey )
		{
			return $result;
		}

		try
		{
			$this->twigEnv->parse($this->twigEnv->tokenize($value));
		}
		catch( TwigSyntaxError $e )
		{
			return ValidationResult::createError('invalidTwigSyntax', ['message' => $e->getMessage()]);
		}
		return ValidationResult::createValid($value);
	}
}
