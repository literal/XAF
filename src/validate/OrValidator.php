<?php
namespace XAF\validate;

class OrValidator implements Validator
{
	/** @var ValidationService */
	protected $validationService;

	public function __construct( ValidationService $validationService )
	{
		$this->validationService = $validationService;
	}

	public function validate( $value )
	{
		$terms = \func_get_args();
		\array_shift($terms);

		foreach( $terms as $term )
		{
			$result = $this->validationService->validate($value, $term);
			if( $result->errorKey === null )
			{
				return $result;
			}
		}

		return $result ?? ValidationResult::createValid($value);
	}

}

