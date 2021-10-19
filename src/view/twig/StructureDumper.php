<?php
namespace XAF\view\twig;

use ReflectionObject;
use ReflectionMethod;
use ReflectionParameter;

/**
 * Dump a data structure to plain text.
 *
 * While similar to PHP's print_r(), this class treats data structures in a more useful way for
 * usage inside a Twig template. It limits the nesting depth, ignores private/protected class members etc.
 */
class StructureDumper
{
	/** @var int */
	protected $maxNestingLevel = 8;

	/**
	 * @param int $level
	 */
	public function setMaxNestingLevel( $level )
	{
		$this->maxNestingLevel = $level;
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	function dump( $value )
	{

		return \trim($this->dumpValue($value, 0));
	}

	/**
	 * @param mixed $value
	 * @param int $nestingLevel
	 * @return string
	 */
	protected function dumpValue( $value, $nestingLevel )
	{
		return \is_array($value) || \is_object($value)
			? $this->dumpComposite($value, $nestingLevel + 1)
			: $this->dumpScalar($value);
	}

	/**
	 * @param mixed $value
	 * @param int $nestingLevel
	 * @return string
	 */
	protected function dumpComposite( $value, $nestingLevel )
	{
		if( $nestingLevel > $this->maxNestingLevel )
		{
			return '...';
		}

		$result = ($nestingLevel > 1 ? "\n" : '');

		$result .= $this->dumpIterableItems($value, $nestingLevel);

		if( \is_object($value) )
		{
			$result .= $this->dumpPublicObjectMethods(new ReflectionObject($value), $nestingLevel);
		}

		return \trim($result) === '' ? 'empty' : $result;
	}

	/**
	 * @param mixed $value
	 * @param int $nestingLevel
	 * @return string
	 */
	protected function dumpIterableItems( $value, $nestingLevel )
	{
		$result = '';
		foreach( $value as $itemKey => $itemValue )
		{
			$result .= $this->getLineIndent($nestingLevel) .
				$itemKey . ': ' . $this->dumpValue($itemValue, $nestingLevel) . "\n";
		}
		return $result;
	}

	/**
	 * @param ReflectionObject $reflectionObject
	 * @param int $nestingLevel
	 * @return string
	 */
	protected function dumpPublicObjectMethods( ReflectionObject $reflectionObject, $nestingLevel )
	{
		$result = '';
		/* @var $reflectionMethod ReflectionMethod */
		foreach( $reflectionObject->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod )
		{
			if( $this->shallDumpMethod($reflectionMethod) )
			{
				$parametersString = $this->formatMethodParameters($reflectionMethod);
				$result .= $this->getLineIndent($nestingLevel) .
					$reflectionMethod->getName() .
					'(' . ($parametersString ? ' ' . $parametersString . ' ' : '') . ')' .
					"\n";
			}
		}

		return $result;
	}

	/**
	 * @param ReflectionMethod $reflectionMethod
	 * @return bool
	 */
	protected function shallDumpMethod( ReflectionMethod $reflectionMethod )
	{
		return $reflectionMethod->isPublic()
			&& \strpos($reflectionMethod->getName(), '__') !== 0;

	}

	/**
	 * @param ReflectionMethod $reflectionMethod
	 * @return string
	 */
	protected function formatMethodParameters( ReflectionMethod $reflectionMethod )
	{
		$result = '';
		$isFirstParam = true;
		/* @var $reflectionParameter ReflectionParameter */
		foreach( $reflectionMethod->getParameters() as $reflectionParameter )
		{
			$result .=
				($reflectionParameter->isOptional() ? ($isFirstParam ? '' : ' ') . '[' : '') .
				($isFirstParam ? '' : ', ') .
				$reflectionParameter->getName() .
				($reflectionParameter->isOptional() ? ']' : '');
			$isFirstParam = false;
		}
		return $result;
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	protected function dumpScalar( $value )
	{
		switch( true )
		{
			case \is_bool($value):
				return $value ? 'true' : 'false';

			case \is_null($value):
				return 'none';

			case \is_string($value):
				return '"' . $value . '"';

			default:
				return \strval($value);
		}
	}

	/**
	 * @param int $nestingLevel
	 * @return string
	 */
	protected function getLineIndent( $nestingLevel )
	{
		return \str_repeat('    ', $nestingLevel - 1);
	}
}
