<?php
namespace XAF\log\error;

use ReflectionObject;
use ReflectionProperty;

class DebugInfoProcessor
{
	/** @var int maximum array nesting level for serialized debug data */
	protected $maxNestingDepth;

	/**
	 * @param int $maxNestingDepth
	 */
	public function __construct( $maxNestingDepth )
	{
		$this->maxNestingDepth = $maxNestingDepth;
	}

	/**
	 * @param mixed $debugInfo
	 * @return string
	 */
	public function serializeDebugInfo( $debugInfo )
	{
		return \serialize($this->processDebugInfo($debugInfo));
	}

	/**
	 * Processes the debug info in a way to permit serialization and avoid excessive size
	 *
	 * @param mixed $debugInfo
	 * @return mixed
	 */
	protected function processDebugInfo( $debugInfo )
	{
		return $this->processValue($debugInfo, 0);
	}

	/**
	 * @param mixed $value
	 * @param int $nestingDepth
	 * @return mixed
	 */
	protected function processValue( $value, $nestingDepth )
	{
		$nestingDepth++;

		switch( true )
		{
			case \is_array($value):
				return $this->getArrayDebugInfo($value, $nestingDepth);

			case \is_object($value):
				return $this->getObjectDebugInfo($value, $nestingDepth);

			case \is_resource($value):
				return '[resource ' . \get_resource_type($value) . ']';
		}

		return $value;
	}

	protected function getArrayDebugInfo( array $value, $nestingDepth )
	{
		if( $nestingDepth >= $this->maxNestingDepth )
		{
			return ['__END_OF_DEBUG_INFO__' => '...'];
		}

		$result = [];
		foreach( $value as $k => $v )
		{
			$result[$k] = $this->processValue($v, $nestingDepth);
		}
		return $result;
	}

	protected function getObjectDebugInfo( $object, $nestingDepth )
	{
		// This element gets special treatment by the error log viewer
		$result = ['__DEBUG_CLASSNAME__' => \get_class($object)];

		if( $nestingDepth >= $this->maxNestingDepth )
		{
			$result['__END_OF_DEBUG_INFO__'] = '...';
			return $result;
		}

		$ro = new ReflectionObject($object);
		foreach( $ro->getProperties() as $rp ) /* @var $rp ReflectionProperty */
		{
			switch( true )
			{
				case $rp->isStatic():
					continue 2;

				case $rp->isProtected():
					$elementName = '#' . $rp->getName();
					break;

				case $rp->isPrivate():
					$elementName = '-' . $rp->getName();
					break;

				default:
					$elementName = '+' . $rp->getName();
			}
			$rp->setAccessible(true);
			$result[$elementName] = $this->processValue($rp->getValue($object), $nestingDepth);
		}

		return $result;
	}
}
