<?php
namespace XAF\di;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\di\DefaultContainer
 */
class DefaultContainerTest extends TestCase
{
	/**
	 * @var DefaultContainer
	 */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new DefaultContainer();
	}

	// ************************************************************************
	// Basic object handling
	// ************************************************************************

	public function testGetUndefinedObjectThrowsException()
	{
		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('unknown object');
		$this->object->get('key');
	}

	public function testCreateUnknownObjectThrowsException()
	{
		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('unknown object');
		$this->object->create('key');
	}

	public function testExplicitlySetObjectIsReturnedByGet()
	{
		$subject = new \stdClass;

		$this->object->set('key', $subject);

		$this->assertSame($subject, $this->object->get('key'));
	}

	public function testExplicitlySetObjectIsReturnedByGetLocal()
	{
		$subject = new \stdClass;

		$this->object->set('key', $subject);

		$this->assertSame($subject, $this->object->getLocal('key'));
	}

	public function testExplicitlySetObjectIsReportedExistent()
	{
		$subject = new \stdClass;

		$this->object->set('key', $subject);

		$this->assertTrue($this->object->exists('key'));
	}

	public function testUndefinedObjectIsNotReportedExistent()
	{
		$this->assertFalse($this->object->exists('key'));
	}

	public function testUndefinedObjectIsUnknown()
	{
		$this->assertFalse($this->object->isKnown('key'));
	}

	public function testExplicitlySetObjectIsKnown()
	{
		$subject = new \stdClass;

		$this->object->set('key', $subject);

		$this->assertTrue($this->object->isKnown('key'));
	}

	// ************************************************************************
	// usage of factory
	// ************************************************************************

	public function testGetCallsFactoryToCreateObject()
	{
		$subject = new \stdClass;
		$factoryMock = $this->buildFactoryMock('key', $subject, 1, 1);
		$this->object->setFactory($factoryMock);

		$result = $this->object->get('key');

		$this->assertSame($subject, $result);
	}

	public function testGetLocalCallsFactoryToCreateObject()
	{
		$subject = new \stdClass;
		$factoryMock = $this->buildFactoryMock('key', $subject, 1, 1);
		$this->object->setFactory($factoryMock);

		$result = $this->object->getLocal('key');

		$this->assertSame($subject, $result);
	}

	public function testGetCallsFactoryOnlyOnceForSameObjectKey()
	{
		$subject = new \stdClass;
		$factoryMock = $this->buildFactoryMock('key', $subject, 1, 1);
		$this->object->setFactory($factoryMock);

		$this->object->get('key');
		$this->object->get('key'); // should not call factory mock again
	}

	public function testGetLocalCallsFactoryOnlyOnceForSameObjectKey()
	{
		$subject = new \stdClass;
		$factoryMock = $this->buildFactoryMock('key', $subject, 1, 1);
		$this->object->setFactory($factoryMock);

		$this->object->getLocal('key');
		$this->object->getLocal('key'); // should not call factory mock again
	}

	public function testCreateCallsFactoryButDoesNotStoreObjectInContainer()
	{
		$subject = new \stdClass;
		$factoryMock = $this->buildFactoryMock('key', $subject, 1, 1);
		$this->object->setFactory($factoryMock);

		$this->object->create('key');

		$this->assertFalse($this->object->exists('key')); // shared instance for 'key' should not exist
	}

	public function testCreateDoesCreateNewObjectOnEachCall()
	{
		$subject = new \stdClass;
		$factoryMock = $this->buildFactoryMock('key', $subject, 2, 2); // expect two calls
		$this->object->setFactory($factoryMock);

		$this->object->create('key');
		$this->object->create('key'); // should call factory mock again
	}

	/**
	 * Test that an object is not reported as existing just because it COULD be
	 * created by the factory
	 */
	public function testCreatableObjectIsNotReportedExistentUnlessCreated()
	{
		$subject = new \stdClass;
		$factoryMock = $this->buildFactoryMock('key', $subject, 0, 0); // expect no calls
		$this->object->setFactory($factoryMock);

		$this->assertFalse($this->object->exists('key'));
	}

	public function testCreatableObjectIsKnown()
	{
		$subject = new \stdClass;
		$factoryMock = $this->buildFactoryMock('key', $subject, 1, 0);
		$this->object->setFactory($factoryMock);

		$this->assertTrue($this->object->isKnown('key'));
	}

	// ************************************************************************
	// container nesting
	// ************************************************************************

	public function testCreateChildContainerReturnsNewContainerObject()
	{
		$childContainer = $this->object->createChildContainer();

		$this->assertTrue($childContainer instanceof DiContainer);
		$this->assertTrue($childContainer !== $this->object);
	}

	/**
	 * If s child container cannot provide a shared object it should fall back on its parent
	 */
	public function testParentContainerObjectIsReportedExistent()
	{
		$subject = new \stdClass;
		$this->object->set('key', $subject);

		$childContainer = $this->object->createChildContainer();

		$this->assertTrue($childContainer->exists('key'));
	}

	public function testParentContainerObjectIsNotReportedExistentLocally()
	{
		$subject = new \stdClass;
		$this->object->set('key', $subject);

		$childContainer = $this->object->createChildContainer();

		$this->assertFalse($childContainer->existsLocally('key'));
	}

	/**
	 * If s child container cannot provide a shared object it should fall back on its parent
	 */
	public function testParentContainerObjectCanBeAccessed()
	{
		$subject = new \stdClass;
		$this->object->set('key', $subject);

		$childContainer = $this->object->createChildContainer();
		$result = $childContainer->get('key');

		$this->assertSame($subject, $result);
	}

	public function testGetLocalDoesNotAccessParentContainer()
	{
		$subject = new \stdClass;
		$this->object->set('key', $subject);
		$childContainer = $this->object->createChildContainer();

		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('unknown object');
		$childContainer->getLocal('key');
	}

	/**
	 * If s child container cannot provide a shared object it should fall back on its parent
	 */
	public function testParentContainerCreatableObjectIsKnown()
	{
		$subject = new \stdClass;
		$factoryMock = $this->buildFactoryMock('key', $subject, 1, 0);
		$this->object->setFactory($factoryMock);

		$childContainer = $this->object->createChildContainer();

		$this->assertTrue($childContainer->isKnown('key'));
	}

	public function testParentContainerCreatableObjectIsNotLocallyKnown()
	{
		$subject = new \stdClass;
		$factoryMock = $this->buildFactoryMock('key', $subject, 0, 0);
		$this->object->setFactory($factoryMock);

		$childContainer = $this->object->createChildContainer();

		$this->assertFalse($childContainer->isKnownLocally('key'));
	}

	/**
	 * If a child container cannot create a non-shared object it should fall back on its parent
	 */
	public function testParentContainerObjectCanBeCreatedThroughChildContainer()
	{
		// Parent container gets a factory that can create 'key'
		$subject = new \stdClass;
		$factoryMock = $this->buildFactoryMock('key', $subject, 1, 1);
		$this->object->setFactory($factoryMock);

		// Child container
		$childContainer = $this->object->createChildContainer();

		$result = $childContainer->create('key');

		// Returned object should be created by child container's factory
		$this->assertSame($subject, $result);
	}

	public function testCreateLocalDoesNotCallParentContainer()
	{
		// Parent container gets a factory that can create 'key'
		$subject = new \stdClass;
		$factoryMock = $this->buildFactoryMock('key', $subject, 0, 0);
		$this->object->setFactory($factoryMock);

		$childContainer = $this->object->createChildContainer();
		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('unknown object');
		$childContainer->createLocal('key');
	}

	/**
	 * If child container can create an object it should do so even if an object with the same
	 * alias already exists in its parent
	 */
	public function testCreatableObjectInChildContainerOverridesExistingObjectInParentContainer()
	{
		// Parent container has an existing object with alias 'key'
		$existingParentObject = new \stdClass;
		$this->object->set('key', $existingParentObject);

		// Child container gets a factory that can create 'key'
		$childContainer = $this->object->createChildContainer();
		$subject = new \stdClass;
		$factoryMock = $this->buildFactoryMock('key', $subject);
		$childContainer->setFactory($factoryMock);

		$result = $childContainer->get('key');

		// Returned object should be created by child container's factory
		$this->assertSame($subject, $result);
	}

	// ************************************************************************
	// Available object reporting
	// ************************************************************************

	public function testAvailableObjectAliases()
	{
		$factoryMock = $this->getMockBuilder(\XAF\di\Factory::class)->getMock();
		$factoryMock->method('getCreatableObjectAliases')->will($this->returnValue(['alias1', 'alias2']));
		$this->object->setFactory($factoryMock);
		// Set one of the creatable objects already registered - should still only appear once in result
		$this->object->set('alias2', new \stdClass);
		// Injected object not creatable though factory, alias should be returned only once although
		// a qualifier-specific instance is registered
		$this->object->set('alias3', new \stdClass);
		$this->object->set('alias3.qualifier', new \stdClass);

		$result = $this->object->getAllLocalObjectAliases();

		$this->assertEquals(['alias1', 'alias2', 'alias3'], $result);
	}

	// ************************************************************************
	// Factory mock creation
	// ************************************************************************

	private function buildFactoryMock( $key, $subject, $expectedCanCreateCallCount = 1, $expectedCreateCallCount = 1 )
	{
		$factoryMock = $this->getMockBuilder(\XAF\di\Factory::class)->getMock();

		if( $expectedCanCreateCallCount > 0 )
		{
			$factoryMock
				->expects($this->exactly($expectedCanCreateCallCount))
				->method('canCreateObject')
				->with($this->equalTo($key))
				->will($this->returnValue(true));
		}

		if( $expectedCreateCallCount > 0 )
		{
			$factoryMock
				->expects($this->exactly($expectedCreateCallCount))
				->method('createObject')
				->with($this->equalTo($key))
				->will($this->returnValue($subject));
		}

		return $factoryMock;
	}

}
