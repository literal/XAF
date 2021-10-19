<?php
namespace XAF\di;

use PHPUnit\Framework\TestCase;
use XAF\test\PhpUnitTestHelper;

require_once 'dummy_services/DummyService.php';
require_once 'dummy_services/DummyServiceWithNamespace.php';

/**
 * @covers \XAF\di\DefaultFactory
 */
class DefaultFactoryTest extends TestCase
{
	/** @var DefaultFactory */
	protected $object;

	/**
	 * @var DiContainer
	 */
	protected $diContainerStub;

	public static function setUpBeforeClass(): void
	{
		\set_include_path(
			\dirname(__FILE__) . \DIRECTORY_SEPARATOR . 'dummy_services' .
			\PATH_SEPARATOR . \get_include_path()
		);
	}

	protected function setUp(): void
	{
		$this->diContainerStub = $this->getMockBuilder('\\XAF\\di\\DiContainer')->getMock();
		$this->object = new DefaultFactory($this->diContainerStub);
	}

	// ************************************************************************
	// Basic object creation
	// ************************************************************************

	public function testCanCreateObject()
	{
		$this->object->setObjectCreationMap([
			'alias' => ['class' => 'MyClass']
		]);

		$result = $this->object->canCreateObject('alias');

		$this->assertTrue($result, 'registered object not stated creatable');
	}

	public function testCannotCreateObject()
	{
		$this->object->setObjectCreationMap([]);

		$result = $this->object->canCreateObject('alias');

		$this->assertFalse($result, 'unregistered object stated creatable');
	}

	public function testCreateObject()
	{
		$this->object->setObjectCreationMap([
			'alias' => ['class' => 'DummyService']
		]);

		$result = $this->object->createObject('alias');

		$this->assertInstanceOf('DummyService', $result);
	}

	public function testCreateObjectWithShorthandNotation()
	{
		$this->object->setObjectCreationMap([
			'alias' => 'DummyService'
		]);

		$result = $this->object->createObject('alias');

		$this->assertInstanceOf('DummyService', $result);
	}

	public function testCreateObjectOfUnknownAliasThrowsException()
	{
		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('unknown alias');
		$this->object->createObject('alias');
	}

	public function testCreateObjectOfUnknownClassThrowsException()
	{
		$this->object->setObjectCreationMap([
			'alias' => ['class' => 'UnknownService']
		]);

		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('undefined class');
		$this->object->createObject('alias');
	}

	public function testCreateObjectWithInvalidDefinitionThrowsException()
	{
		$this->object->setObjectCreationMap([
			'alias' => []
		]);

		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('neither class name nor creator specified');
		$this->object->createObject('alias');
	}

	// ************************************************************************
	// Object creation with qualifiers
	// ************************************************************************

	public function testCanCreateObjectWithQualifier()
	{
		$this->object->setObjectCreationMap(
			['alias' => ['class' => 'MyClass']
		]);

		$result = $this->object->canCreateObject('alias.qualifier');

		$this->assertTrue($result, 'object not reported creatable');
	}

	public function testCreateObjectWithQualifier()
	{
		$this->object->setObjectCreationMap(
			['alias' => ['class' => 'DummyService']
		]);

		// The qualifier is not actually used for anything here - we are only testing that
		// the factory does not regard '.qualifier' as part of the object alias
		$result = $this->object->createObject('alias.qualifier');

		$this->assertInstanceOf('DummyService', $result);
	}

	public function testCreateObjectOfUnknownAliasWithQualifierThrowsException()
	{
		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('unknown alias');
		$this->object->createObject('alias.qualifier');
	}

	public function testMostSpecificObjectIsCreated()
	{
		$this->object->setObjectCreationMap([
			'alias' => ['class' => 'MyClass'],
			'alias.foo' => ['class' => 'DummyService']
		]);

		// As there is a key 'alias.foo' in the creation map it should be chosen over
		// 'alias' because it is the more specific match
		$result = $this->object->createObject('alias.foo');

		$this->assertInstanceOf('DummyService', $result);
	}

	public function testUnqualifiedObjectIsCreatedIfNoQualifierMatch()
	{
		$this->object->setObjectCreationMap([
			'alias.foo' => ['class' => 'MyClass'],
			'alias' => ['class' => 'DummyService']
		]);

		// There is no 'alias.bar' in the map, so 'alias' shall be created and
		// 'bar' be used as a qualifier
		$result = $this->object->createObject('alias.bar');

		$this->assertInstanceOf('DummyService', $result);
	}

	public function testSpecificObjectAndMultiPartQualifier()
	{
		$this->object->setObjectCreationMap([
			'alias.foo' => ['class' => 'DummyService']
		]);

		// '.foo' should be used to look up the definition in the creation map while there is no match
		// for '.bar' in the map, so 'bar' becomes the qualifier (which is not being used in this test)
		$result = $this->object->createObject('alias.foo.bar');

		$this->assertInstanceOf('DummyService', $result);
	}

	public function testDollarSignForwardsQualifierToDependency()
	{
		$objectToInject = new \stdClass;
		$this->diContainerStub
			->expects($this->once())
			->method('get')
			->with($this->equalTo('ObjectAlias.qualifier')) // << expect the qualifier to be passed on
			->will($this->returnValue($objectToInject));
		$this->object->setObjectCreationMap([
			// the '.$' should forward the qualifier
			'alias' => ['class' => 'DummyService', 'args' => ['@ObjectAlias.$']]
		]);

		$this->object->createObject('alias.qualifier');
	}

	public function testOnlyUnmatchedPartOfQualifierIsPassedToDependency()
	{
		$objectToInject = new \stdClass;
		$this->diContainerStub
			->expects($this->once())
			->method('get')
			->with($this->equalTo('ObjectAlias.bar')) // << expect the *remaining part of* the qualifier to be passed on
			->will($this->returnValue($objectToInject));
		$this->object->setObjectCreationMap([
			'alias.foo' => ['class' => 'DummyService', 'args' => ['@ObjectAlias.$']]
		]);

		// '.foo' should be used to look up the definition in the creation map while there is no match
		// for '.bar' in the map, so 'bar' becomes the qualifier argument passed on with '.$'
		$this->object->createObject('alias.foo.bar');
	}

	// ************************************************************************
	// Loading of class files
	// ************************************************************************

	public function testCreateObjectFromMissingClassFile()
	{
		$this->object->setObjectCreationMap([
			'alias' => ['class' => 'Invalid', 'file' => 'missing']
		]);

		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('failed to include class file');
		@$this->object->createObject('alias');
	}

	public function testCreateObjectWithExplicitClassFile()
	{
		$this->object->setObjectCreationMap([
			'alias' => ['class' => 'SubdirDummyService', 'file' => 'subdir/SubdirDummyService.php']
		]);

		$result = $this->object->createObject('alias');

		$this->assertInstanceOf('SubdirDummyService', $result);
	}

	// ************************************************************************
	// namespace support
	// ************************************************************************

	public function testCreateObjectWithNamespace()
	{
		$this->object->setObjectCreationMap([
			'alias' => ['class' => '\\DummyNamespace\\DummyServiceWithNamespace']
		]);

		$result = $this->object->createObject('alias');

		$this->assertInstanceOf('\\DummyNamespace\\DummyServiceWithNamespace', $result);
	}

	// ************************************************************************
	// arguments passed to constructors
	// ************************************************************************

	public function testCreateObjectWithoutArgs()
	{
		$this->object->setObjectCreationMap([
			'alias' => ['class' => 'DummyService']
		]);

		$result = $this->object->createObject('alias');

		$this->assertEquals(0, \sizeof($result->constructorArgs), 'unexpected arguments passed to created object');
	}

	public function testCreateObjectWithSharedObjectInjection()
	{
		$objectToInject = new \stdClass;
		$this->diContainerStub
			->expects($this->once())
			->method('get')
			->with($this->equalTo('ObjectAlias'))
			->will($this->returnValue($objectToInject));
		$this->object->setObjectCreationMap([
			'alias' => ['class' => 'DummyService', 'args' => ['@ObjectAlias']]
		]);

		$result = $this->object->createObject('alias');

		$this->assertEquals(1, \sizeof($result->constructorArgs), 'dependency not injected into created object');
		$this->assertSame($objectToInject, $result->constructorArgs[0]);
	}

	public function testCreateObjectWithExclusiveObjectInjection()
	{
		$objectToInject = new \stdClass;
		$this->diContainerStub
			->expects($this->once())
			->method('create')
			->with($this->equalTo('ObjectAlias'))
			->will($this->returnValue($objectToInject));
		$this->object->setObjectCreationMap([
			'alias' => ['class' => 'DummyService', 'args' => ['#ObjectAlias']]
		]);

		$result = $this->object->createObject('alias');

		$this->assertEquals(1, \sizeof($result->constructorArgs), 'dependency not injected into created object');
		$this->assertSame($objectToInject, $result->constructorArgs[0]);
	}

	public function testCreateObjectWithStaticArgs()
	{
		for( $i = 1; $i < 7; $i++ )
		{
			$this->object->setObjectCreationMap([
				'alias' => ['class' => 'DummyService', 'args' => \range(1, $i)]
			]);

			$result = $this->object->createObject('alias');

			$this->assertEquals($i, \sizeof($result->constructorArgs), 'incorrect number of arguments passed to constructor');
		}
	}

	/**
	 * In the object creation map, 'args' can be a single value instead of an array if only one argument
	 * is to be passed to a new object
	 */
	public function testCreateObjectWithShorthandArgNotation()
	{
		$this->object->setObjectCreationMap([
			'alias' => ['class' => 'DummyService', 'args' => 'not an array']
		]);

		$result = $this->object->createObject('alias');

		$this->assertEquals('not an array', $result->constructorArgs[0]);
	}

	/**
	 * The special argument '@Container' should yield the current container object
	 */
	public function testContainerArg()
	{
		$this->object->setObjectCreationMap([
			'alias' => ['class' => 'DummyService', 'args' => ['@Container']]
		]);

		$result = $this->object->createObject('alias');

		$this->assertSame($this->diContainerStub, $result->constructorArgs[0]);
	}

	/**
	 * If a qualifier is used in the object key, it can be passed to the new object via the special argument '$'
	 */
	public function testDollarSignArgumentYieldsObjectQualifier()
	{
		$this->object->setObjectCreationMap([
			'alias' => ['class' => 'DummyService', 'args' => ['$']]
		]);

		$result = $this->object->createObject('alias.qualifier');

		$this->assertEquals('qualifier', $result->constructorArgs[0]);
	}

	/**
	 * If a qualifier is used in the object key, it can be passed to the new object via the special argument '$'
	 */
	public function testDollarSignArgumentYieldsOnlyUnmatchedPartOfObjectQualifier()
	{
		$this->object->setObjectCreationMap([
			'alias.foo' => ['class' => 'DummyService', 'args' => ['$']]
		]);

		// The first qualifier 'foo' should be "eaten up" by the object alias containing it
		$result = $this->object->createObject('alias.foo.bar');

		// ...so we expect only the remaining 'bar' to be the result of the '$' argument
		$this->assertEquals('bar', $result->constructorArgs[0]);
	}

	public function testMoreThan11ConstructorArgumentsThrowException()
	{
		$this->object->setObjectCreationMap([
			'alias' => ['class' => 'DummyService', 'args' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]]
		]);

		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('too many constructor arguments');
		$this->object->createObject('alias');
	}

	// ************************************************************************
	// object creation callback
	// ************************************************************************

	public function testCreateObjectByCallback()
	{
		$this->object->setObjectCreationMap([
			'alias' => ['creator' => [$this, 'objectCreationCallback']]
		]);

		$result = $this->object->createObject('alias');

		$this->assertInstanceOf('stdClass', $result);
		$this->assertEquals(0, \sizeof($result->creationArgs), 'unexpected arguments passed to object creation callback');
	}

	public function testCreateObjectByCallbackWithLiteralArgs()
	{
		$this->object->setObjectCreationMap([
			'alias' => [
				'creator' => [$this, 'objectCreationCallback'],
				'args' => ['foo', 'bar']
			]
		]);

		$result = $this->object->createObject('alias');

		$this->assertEquals(2, \sizeof($result->creationArgs), 'wrong number of arguments passed to object creation callback');
		$this->assertEquals('foo', $result->creationArgs[0]);
		$this->assertEquals('bar', $result->creationArgs[1]);
	}

	public function testCreateObjectByCallbackWithObjectArg()
	{
		$objectToPass = new \stdClass;
		$this->diContainerStub
			->expects($this->once())
			->method('get')
			->with($this->equalTo('ObjectAlias'))
			->will($this->returnValue($objectToPass));
		$this->object->setObjectCreationMap([
			'alias' => [
				'creator' => [$this, 'objectCreationCallback'],
				'args' => ['@ObjectAlias']
			]
		]);

		$result = $this->object->createObject('alias');

		$this->assertEquals(1, \sizeof($result->creationArgs), 'wrong number of arguments passed to object creation callback');
		$this->assertSame($objectToPass, $result->creationArgs[0]);
	}

	public function objectCreationCallback()
	{
		$object = new \stdClass;
		$object->creationArgs = \func_get_args();
		return $object;
	}

	public function testInvalidObjectCreationCallbackThrowsException()
	{
		$this->object->setObjectCreationMap([
			'alias' => ['creator' => 'not_a_known_function']
		]);

		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('not a valid callable');

		$this->object->createObject('alias');
	}

	// ************************************************************************
	// container nesting
	// ************************************************************************

	public function testCreateObjectWithChildContainer()
	{
		$this->object->setObjectCreationMap([
			'alias' => [
				'class' => 'DummyService',
				// pass container to dummy service for later inspection in the assert
				'args' => '@Container',
				// the 'submap' element should trigger the child container creation (even if empty)
				'submap' => []
			]
		]);
		$childContainerStub = $this->getMockBuilder(\XAF\di\DiContainer::class)->getMock();
		$this->diContainerStub
			->expects($this->once())
			->method('createChildContainer')
			->will($this->returnValue($childContainerStub));

		$result = $this->object->createObject('alias');

		$this->assertSame($result->constructorArgs[0], $childContainerStub, 'child container not created for object with submap');
	}

	// ************************************************************************
	// Creatable object reporting
	// ************************************************************************

	public function testCreatableObjectAliases()
	{
		$this->object->setObjectCreationMap([
			// B beore A to verify map order is kept
			'aliasB' => ['class' => 'MyClassB'],
			'aliasA' => ['class' => 'MyClassA'],
			// Qualifiers should be ignored and 'aliasA' be returned only once
			'aliasA.qualifier' => ['class' => 'MyClassA2'],
			'aliasA.qualifier.sub' => ['class' => 'MyClassA3']
		]);

		$result = $this->object->getCreatableObjectAliases();

		$this->assertEquals(['aliasB', 'aliasA'], $result);
	}
}
