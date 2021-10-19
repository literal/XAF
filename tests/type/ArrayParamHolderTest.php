<?php
namespace XAF\type;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\type\ArrayParamHolder
 */
class ArrayParamHolderTest extends TestCase
{
	public function testParamsCanBeSetByPassingHashToConstructor()
	{
		$object = new ArrayParamHolder(['foo' => 'bar']);

		$result = $object->get('foo');

		$this->assertEquals('bar', $result);
	}

	public function testSetValueIsReturnedByGet()
	{
		$object = new ArrayParamHolder;

		$object->set('foo', 'bar');
		$result = $object->get('foo');

		$this->assertEquals('bar', $result);
	}

	public function testGetMissingParamReturnsNullByDefault()
	{
		$object = new ArrayParamHolder;

		$result = $object->get('foo');

		$this->assertNull($result);
	}

	public function testGetMissingParamReturnsDefaultIfSpecified()
	{
		$object = new ArrayParamHolder;

		$result = $object->get('foo', 'default');

		$this->assertEquals('default', $result);
	}

	public function testRemoveRemovesElement()
	{
		$object = new ArrayParamHolder;
		$object->set('foo', 'bar');

		$object->remove('foo');
		$result = $object->get('foo');

		$this->assertNull($result);
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testRemoveMissingParamThrowsNoError()
	{
		$object = new ArrayParamHolder;

		$object->remove('foo');
	}

	public function testGetRequiredBehavesLikeGet()
	{
		$object = new ArrayParamHolder;

		$object->set('foo', 'bar');
		$this->assertEquals($object->get('foo'), $object->getRequired('foo'));
	}

	public function testGetRequiredForMissingParamThrowsError()
	{
		$object = new ArrayParamHolder;

		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('required parameter does not exist');
		$object->getRequired('foo');
	}

	public function testGetIntConvertsStringToInt()
	{
		$object = new ArrayParamHolder;

		$object->set('foo', '2');

		$this->assertSame(2, $object->getInt('foo'));
		$this->assertSame(2, $object->getRequiredInt('foo'));
	}

	public function testGetRequiredIntForMissingParamThrowsError()
	{
		$object = new ArrayParamHolder;

		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('required parameter does not exist');
		$object->getRequiredInt('foo');
	}

	public function testGetBoolConvertsStringToBool()
	{
		$object = new ArrayParamHolder;

		$object->set('foo', 'yes');
		$this->assertSame(true, $object->getBool('foo'));
		$this->assertSame(true, $object->getRequiredBool('foo'));

		$object->set('foo', 'no');
		$this->assertSame(false, $object->getBool('foo'));
		$this->assertSame(false, $object->getRequiredBool('foo'));
	}

	public function testGetBoolConvertsNumberToBool()
	{
		$object = new ArrayParamHolder;

		$object->set('foo', 27);
		$this->assertSame(true, $object->getBool('foo'));
		$this->assertSame(true, $object->getRequiredBool('foo'));

		$object->set('foo', 0);
		$this->assertSame(false, $object->getBool('foo'));
		$this->assertSame(false, $object->getRequiredBool('foo'));
	}

	public function testGetRequiredBoolForMissingParamThrowsError()
	{
		$object = new ArrayParamHolder;

		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('required parameter does not exist');
		$object->getRequiredBool('foo');
	}

	public function testGetArrayTurnsScalarIntoArray()
	{
		$object = new ArrayParamHolder;
		$object->set('foo', 'bar');

		$this->assertSame(['bar'], $object->getArray('foo'));
		$this->assertSame(['bar'], $object->getRequiredArray('foo'));
	}

	public function testGetArrayLeavesArrayAsIs()
	{
		$object = new ArrayParamHolder;
		$object->set('foo', ['bar']);

		$this->assertSame(['bar'], $object->getArray('foo'));
		$this->assertSame(['bar'], $object->getRequiredArray('foo'));
	}

	public function testGetRequiredArrayForMissingParamThrowsError()
	{
		$object = new ArrayParamHolder;

		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('required parameter does not exist');
		$object->getRequiredArray('foo');
	}

	public function testMergeOverwritesIfSameKey()
	{
		$object = new ArrayParamHolder(['foo' => 'foo']);

		$object->merge(['foo' => 'bar']); // should overwrite existing 'foo' => 'foo'
		$result = $object->get('foo');

		$this->assertEquals('bar', $result);
	}

	public function testMergeKeepsExistingElements()
	{
		$object = new ArrayParamHolder(['foo' => 'foo']);

		$object->merge(['bar' => 'bar']);
		$result = $object->get('foo');

		$this->assertEquals('foo', $result);
	}

}
