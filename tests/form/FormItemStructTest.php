<?php
namespace XAF\form;

use PHPUnit\Framework\TestCase;

use XAF\validate\ValidationServiceStub;

require_once __DIR__ . '/stubs/ValidationServiceStub.php';

/**
 * @covers \XAF\form\FormItemStruct
 */
class FormItemStructTest extends TestCase
{
	/** @var FormItemStruct */
	protected $object;

	/** @var ValidationServiceStub */
	private $validationServiceStub;

	protected function setUp(): void
	{
		$this->validationServiceStub = new ValidationServiceStub();
		$this->object = new FormItemStruct($this->validationServiceStub);
	}

	public function testMissingStructElementInDefinitionThrowsException()
	{
		$this->expectException(\XAF\exception\SystemError::class);
		$this->object->setSchema([]);
	}

	public function testSettingScalarValueDoesNothing()
	{
		$this->object->setSchema([
			'struct' => [
				'foo' => ''
			]
		]);

		$this->object->setValue('foo');

		$this->assertNull($this->object['foo']->getValue());
	}

	/**
	 * As countability is implemented in the common super class,
	 * this test is for both field arrays and hashes
	 */
	public function testItemsAreCountableThroughObject()
	{
		$this->object->setSchema([
			'struct' => [
				'foo' => '',
				'bar' => '',
				'boom' => ''
			]
		]);

		$itemCount = \count($this->object);

		$this->assertEquals(3, $itemCount);
	}

	/**
	 * As traversability is implemented in the common super class,
	 * this test is for both field arrays and hashes
	 */
	public function testItemsAreTraversableThroughObject()
	{
		$this->object->setSchema([
			'struct' => [
				'foo' => '',
				'bar' => '',
				'boom' => '',
			]
		]);

		$loopCount = 0;
		foreach( $this->object as $field )
		{
			$loopCount++;
		}

		$this->assertEquals(3, $loopCount);
	}

	public function testItemsAreNamedAsArrayElementsIfArrayHasName()
	{
		$this->object->setName('composite');
		$this->object->setSchema([
			'struct' => [
				'foo' => '',
				'bar' => ''
			]
		]);

		$this->assertEquals('composite[foo]', $this->object['foo']->getName());
		$this->assertEquals('composite[bar]', $this->object['bar']->getName());
	}

	public function testSetValueReturnsTrueIfAtLeastOneValueMapsToAnExistingField()
	{
		$this->object->setSchema([
			'struct' => [
				'foo' => '',
				'bar' => ''
			]
		]);

		$result = $this->object->setValue(['foo' => 'value']);

		$this->assertTrue($result);
	}

	public function testSetValueReturnsFalseIfValueContainsNoExistingFieldKeys()
	{
		$this->object->setSchema([
			'struct' => [
				'foo' => '',
				'bar' => ''
			]
		]);

		$result = $this->object->setValue(['baz' => 'value']);

		$this->assertFalse($result);
	}
}
