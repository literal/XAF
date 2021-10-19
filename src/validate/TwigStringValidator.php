<?php
namespace XAF\validate;

use Twig_Environment;
use Twig_Error_Syntax;

/**
 * @errorKey invalidTwigSyntax(message)
 */
class TwigStringValidator extends NotEmptyValidator
{
	/** @var Twig_Environment */
	private $twigEnv;

	/**
	 * @param Twig_Environment $twigEnv
	 */
	public function __construct( Twig_Environment $twigEnv )
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
		catch( Twig_Error_Syntax $e )
		{
			return ValidationResult::createError('invalidTwigSyntax', ['message' => $e->getMessage()]);
		}
		return ValidationResult::createValid($value);
	}
}
