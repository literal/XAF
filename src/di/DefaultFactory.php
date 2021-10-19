<?php
namespace XAF\di;

use XAF\exception\SystemError;

/**
 * Create instances of model-, infrastructure- and helper-classes based on
 * an object creation map
 */
class DefaultFactory implements Factory
{
	/**
	 * @var array See method setObjectCreationMap()
	 */
	private $objectCreationMap = [];

	/**
	 * @var DiContainer
	 */
	private $diContainer;

	/**
	 * @param DiContainer $container used to get any objects injected into other objects upon creation
	 */
	public function __construct( DiContainer $container )
	{
		$this->diContainer = $container;
	}

	/**
	 * The subtle difference between object keys and aliasses:
	 *
	 *   The alias is the string under which an object is defined in the map.
	 *   The key is an object identifier requested from the outside.
	 *
	 *   The key may also contain a qualifier, separated from the alias by a dot ("alias.qualifier").
	 *   The qualifier is a value distinguishing multiple instances of the same class and will be passed
	 *   to an instance during creation. Often a language code is used as a qualifier to
	 *   manage instances responsible for the same task in a different language.
	 *
	 *   BUT: The alias can contain dots, too! If a key with a dot is requested and there is a matching
	 *   alias in the object creation map, the part after the dot will NOT be used as a qualifier but as
	 *   part of the alias instead!
	 *   This way the object creation map can freely specify whether a qualified object (i.e. a key with one
	 *   or more dots) maps to just another instance of a common map entry or a separate entry for a different
	 *   class.
	 *
	 *   To the user of the factory this is transparent. If an object key of 'Translator.de.ch' ist requested,
	 *   this could internally be mapped to:
	 *   - 'Translator' and a qualifier of 'de.ch' passed to the translator's constructor
	 *   - 'Translator.de' and a qualifier of 'ch' passed to the translator's constructor
	 *   - 'Translator.de.ch' and no qualifier passed to the translator's constructor
	 *   The maping depends on whether an alias of 'Translator.de' or 'Translator.de.ch' exists in the
	 *   object creation map.
	 *
	 * Format of the object creation map:
	 *
	 * {
	 *     // the alias by which the object can be aquired through the create()-method,
	 *     // in most cases this would equal the class name
	 *   <object alias>: {
	 *
	 *       // Name of the created object's class, can include namespace
	 *       // (either 'class' or 'creator' is required)
	 *     'class': <string>,
	 *
	 *       // Callback for creation of the object
	 *     'creator': <php callable>,
	 *
	 *       // Optional name of class file to be included if class is not autoloaded
	 *       // (include_once() will be used, so don't fear multiple inclusion)
	 *     'file': <string>,
	 *
	 *       // Optional array of arguments for the new object's constructor or the
	 *       // creation callback function (depending on whether 'class' or 'creator' is specified).
	 *       //
	 *       // String arguments starting with @ or # mark dependencies (i.e. refer object keys known to the DI container),
	 *       // so the named object will be passed as an argument instead of the string
	 *       //  - @ is for a shared dependency, the container's get()-method will be called to get the object
	 *       //  - # is for a private dependency, the container's create()-method will be called to get the object
	 *       // A trailing '.$' will add the current object's qualifier (if any, see below) to the object key.
	 *       //
	 *       // The special argument '@Container' will be replaced by the current DI container. Use with care,
	 *       // as passing the container to clients breaks the basic idea of dependency injection. But there
	 *       // are valid use cases like a front controller which needs to have access to any number of
	 *       // more specialized controllers only one of which is to be crated depending on the HTTP request.
	 *       //
	 *       // A single $ character will be replaced by the optional qualifier-part of the requested object key.
	 *       // E.g. if the alias 'SpellChecker' is defined in the object creation map and the object key
	 *       // 'SpellChecker.de.DE' is requested, the '$' argument will be replaced by 'de.DE'. If only
	 *       // 'SpellChecker' is requested, the '$'-argument will resolve to null.
	 *       //
	 *       // For convenience a single value can be used instead of an array if there is only one argument
	 *       // to be passed to the constructor or creator callback.
	 *     'args': <array|mixed>,
	 *
	 *       // Optional, spawn a new child container from the current DI container and tie to it a new
	 *       // factory which will use this object creation map. All objects created by the child container's
	 *       // factory will also get their dependencies from the child container.
	 *     'submap': <object creation map>
	 *   },
	 *   ...
	 * }
	 *
	 * As a shorthand for simple cases where only the 'class' element is needed, a string containing
	 * the class name can be given as a map entry instead of the hashmap above
	 *
	 * @param array $creationMap
	 */
	public function setObjectCreationMap( array $creationMap )
	{
		$this->objectCreationMap = $creationMap;
	}

	// ************************************************************************
	// Implementation of interface Factory
	// ************************************************************************

	/**
	 * @return array
	 */
	public function getCreatableObjectAliases()
	{
		$result = [];
		foreach( \array_keys($this->objectCreationMap) as $objectKey )
		{
			$objectAlias = \explode('.', $objectKey, 2)[0];
			if( !\in_array($objectAlias, $result) )
			{
				$result[] = $objectAlias;
			}
		}
		return $result;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function canCreateObject( $key )
	{
		list($mapKey, $qualifier) = $this->splitObjectKey($key);
		return isset($this->objectCreationMap[$mapKey]);
	}

	/**
	 * @param string $key if there is a dot in the key, the part left of it is the object alias and the rest is a qualifier
	 * @return object
	 */
	public function createObject( $key )
	{
		list($mapKey, $qualifier) = $this->splitObjectKey($key);

		if( !isset($this->objectCreationMap[$mapKey]) )
		{
			throw new SystemError('unknown alias', $mapKey);
		}

		$this->normalizeObjectDefinition($mapKey);

		if( isset($this->objectCreationMap[$mapKey]['file']) )
		{
			$this->loadClassFile($mapKey);
		}

		if( isset($this->objectCreationMap[$mapKey]['class']) )
		{
			return $this->createObjectDirectly($mapKey, $qualifier);
		}

		if( isset($this->objectCreationMap[$mapKey]['creator']) )
		{
			return $this->createObjectByCallback($mapKey, $qualifier);
		}

		throw new SystemError('neither class name nor creator specified', $mapKey);
	}

	/**
	 * Split object key into an object alias and a qualifier
	 *
	 * @param string $key
	 * @return array [<mapKey>, <qualifier>]
	 */
	private function splitObjectKey( $key )
	{
		if( isset($this->objectCreationMap[$key]) )
		{
			return [$key, null];
		}

		// chop off qualifier parts from the end of the key until the remaining left side matches
		// a key in the object creation map
		$parts = \explode('.', $key);
		$qualifier = '';
		while( \sizeof($parts) > 1 )
		{
			$qualifier = \array_pop($parts) . ($qualifier !== '' ? '.' : '') . $qualifier;
			$mapKey = \implode('.', $parts);
			if( isset($this->objectCreationMap[$mapKey]) )
			{
				return [$mapKey, $qualifier];
			}
		}

		return [$key, null];
	}

	/**
	 * @param string $mapKey
	 */
	private function normalizeObjectDefinition( $mapKey )
	{
		// Resolve shorthand syntax (object definition is a classname-string instead of a hash)
		if( !\is_array($this->objectCreationMap[$mapKey]) )
		{
			$this->objectCreationMap[$mapKey] = ['class' => $this->objectCreationMap[$mapKey]];
		}

		// Resolve single construction argument specified as scalar
		if( isset($this->objectCreationMap[$mapKey]['args']) && !\is_array($this->objectCreationMap[$mapKey]['args']) )
		{
			$this->objectCreationMap[$mapKey]['args'] = [$this->objectCreationMap[$mapKey]['args']];
		}
	}

	/**
	 * @param string $mapKey
	 */
	private function loadClassFile( $mapKey )
	{
		$classFile = $this->objectCreationMap[$mapKey]['file'];
		if( false === include_once $classFile )
		{
			// @codeCoverageIgnoreStart
			throw new SystemError('failed to include class file', $classFile);
			// @codeCoverageIgnoreEnd
		}
	}

	/**
	 * @param string $mapKey
	 * @param string|null $qualifier
	 * @return object
	 */
	private function createObjectDirectly( $mapKey, $qualifier )
	{
		$className = $this->objectCreationMap[$mapKey]['class'];
		$constructorArgs = $this->getConstructionArgs($mapKey, $qualifier);
		return $this->instantiateObject($className, $constructorArgs);
	}

	/**
	 * @param string $mapKey
	 * @param string|null $qualifier
	 * @return object
	 */
	private function createObjectByCallback( $mapKey, $qualifier )
	{
		$callback = $this->objectCreationMap[$mapKey]['creator'];
		if( !\is_callable($callback) )
		{
			throw new SystemError('creator not a valid callable', $mapKey);
		}
		$args = $this->getConstructionArgs($mapKey, $qualifier);
		return \call_user_func_array($callback, $args);
	}

	/**
	 * @param string $mapKey
	 * @param string|null $qualifier
	 * @return array
	 */
	private function getConstructionArgs( $mapKey, $qualifier )
	{
		$objectDef = $this->objectCreationMap[$mapKey];
		if( !isset($objectDef['args']) )
		{
			return [];
		}

		$container = $this->getContainerForNewObject($mapKey);
		$result = [];
		foreach( $objectDef['args'] as $arg )
		{
			if( \is_string($arg) && \strlen($arg) > 1 )
			{
				if( $arg === '@Container' )
				{
					$result[] = $container;
					continue;
				}
				if( $arg[0] === '@' )
				{
					$objectKey = $this->buildDependencyKey($arg, $qualifier);
					$result[] = $container->get($objectKey);
					continue;
				}
				if( $arg[0] === '#' )
				{
					$objectKey = $this->buildDependencyKey($arg, $qualifier);
					$result[] = $container->create($objectKey);
					continue;
				}
			}

			if( $arg === '$' )
			{
				$result[] = $qualifier;
				continue;
			}

			$result[] = $arg;
		}

		return $result;
	}

	/**
	 * Determines/creates the (child) DI container to pass to a new object
	 *
	 * @param string $mapKey
	 * @return DiContainer
	 */
	private function getContainerForNewObject( $mapKey )
	{
		if( isset($this->objectCreationMap[$mapKey]['submap']) )
		{
			$childContainer = $this->diContainer->createChildContainer();

			$childFactory = new self($childContainer);
			$childFactory->setObjectCreationMap($this->objectCreationMap[$mapKey]['submap']);

			$childContainer->setFactory($childFactory);
			return $childContainer;
		}

		return $this->diContainer;
	}

	/**
	 * Compute the key of an object to be injected from an argument expression in the object creation map
	 *
	 * @param string $argExpression the @- or #-expression for an injected object
	 * @param string|null $currentQualifier qualifier of the object which gets the injection
	 *     (because the qualifier may be passed on to it's dependencies)
	 * @return string
	 */
	private function buildDependencyKey( $argExpression, $currentQualifier )
	{
		$objectKey = \substr($argExpression, 1);
		$objectKey = \str_replace('.$', isset($currentQualifier) ? '.' . $currentQualifier : '', $objectKey);
		return $objectKey;
	}

	/**
	 * Work-around for creating an object with a variable number of constructor arguments
	 *
	 * Only up to ten arguments are supported!
	 *
	 * Unfortunately, there is no way to call a constructor with a variable
	 * number of arguments like call_user_func_array() can do for functions
	 *
	 * @param string $className
	 * @param array $args
	 * @return object
	 */
	private function instantiateObject( $className, array $args )
	{
		if( !\class_exists($className) )
		{
			throw new SystemError('undefined class', $className);
		}

		switch( \sizeof($args) )
		{
			case 0:
				return new $className();
			case 1:
				return new $className($args[0]);
			case 2:
				return new $className($args[0], $args[1]);
			case 3:
				return new $className($args[0], $args[1], $args[2]);
			case 4:
				return new $className($args[0], $args[1], $args[2], $args[3]);
			case 5:
				return new $className($args[0], $args[1], $args[2], $args[3], $args[4]);
			case 6:
				return new $className($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
			case 7:
				return new $className($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]);
			case 8:
				return new $className($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7]);
			case 9:
				return new $className($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8]);
			case 10:
				return new $className($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8], $args[9]);
			case 11:
				return new $className($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8], $args[9], $args[10]);
			default:
				throw new SystemError('too many constructor arguments', $args);
		}
	}
}
