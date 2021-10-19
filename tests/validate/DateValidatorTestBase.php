<?php
namespace XAF\validate;

require_once 'ValidationTestBase.php';

class DateValidatorTestBase extends ValidationTestBase
{
	/**
	 * @param string $expectedDate Y-m-d
	 * @param ValidationResult $result
	 */
	protected function assertValidationResult( $expectedDate, ValidationResult $result )
	{
		$this->assertValidationPassed($result);
		$this->assertInstanceOf('DateTime', $result->value);
		$this->assertSame($expectedDate, $result->value->format('Y-m-d'));
	}
}

