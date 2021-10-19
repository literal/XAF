<?php
namespace XAF\form;

use PHPUnit\Framework\TestCase;

use XAF\validate\ValidationServiceStub;

require_once __DIR__ . '/stubs/ValidationServiceStub.php';

/**
 * @covers \XAF\form\FormItemArray
 */
class FormItemArrayTest extends TestCase
{
	/**
	 * @var FormItemArray
	 */
	protected $object;

	/**
	 * @var ValidationServiceStub
	 */
	private $validationServiceStub;

	protected function setUp(): void
	{
		$this->validationServiceStub = new ValidationServiceStub();
		$this->object = new FormItemArray($this->validationServiceStub);
	}

	public function testEachElementInDefaultsArrayYieldsAnItem()
	{
		$this->object->setSchema([
			'array' => '',
			'default' => ['foo', 'bar']
		]);

		$this->object->setDefault();

		$this->assertEquals('foo', $this->object[0]->getValue());
		$this->assertEquals('bar', $this->object[1]->getValue());
	}

	public function testItemsAreCreatedBySetValue()
	{
		$this->object->setSchema([
			'array' => ''
		]);

		$this->object->setValue(['foo', 'bar']);

		$this->assertEquals('foo', $this->object[0]->getValue());
		$this->assertEquals('bar', $this->object[1]->getValue());
	}

	public function testThereAreNoItemsAfterSettingEmptyValue()
	{
		$this->object->setSchema([
			'array' => '',
		]);

		$this->object->setValue(['foo', 'bar']);
		$this->object->setValue(null);

		$this->assertEquals(0, \count($this->object));
	}

	public function testThereAreNoItemsAfterSettingScalarValue()
	{
		$this->object->setSchema([
			'array' => ''
		]);

		$this->object->setValue(['foo', 'bar']);
		$this->object->setValue('foo');

		$this->assertEquals(0, \count($this->object));
	}

	public function testMissingArraySchemaElementThrowsException()
	{
		$this->expectException(\XAF\exception\SystemError::class);
		$this->object->setSchema([]);
	}

	public function testScalarDefaultThrowsException()
	{
		$this->object->setSchema([
			'array' => '',
			'default' => 'foo'
		]);

		$this->expectException(\XAF\exception\SystemError::class);
		$this->object->setDefault();
	}

	public function testNoItemsAreCreatedWithoutDefault()
	{
		$this->object->setSchema([
			'array' => ''
		]);

		$this->object->setDefault();

		$this->assertEquals(0, \count($this->object));
	}

	public function testKeysAreDiscardedWhenValuesAreSet()
	{
		$this->object->setSchema([
			'array' => ''
		]);

		$this->object->setValue(['foo' => 'foo']);

		$this->assertFalse(isset($this->object['foo']));
		$this->assertEquals('foo', $this->object[0]->getValue());
	}

	public function testElementsAreValidated()
	{
		$this->object->setSchema([
			'array' => [
				'rule' => 'fail'
			]
		]);
		// There is nothing to validate if no fields are present - and only
		// setting defaults or values will actually create fields,
		// so we set one value
		$this->object->setValue(['foo']);

		$this->object->validate();

		$this->assertTrue($this->object->hasError());
	}

	public function testItemKeysAreNotKept()
	{
		$this->object->setSchema([
			'array' => ''
		]);
		$this->object->setValue([3 => 'foo', 1 => 'bar', 2 => 'boom']);

		$this->assertEquals('foo', $this->object[0]);
	}

	public function testItemsAreCountableThroughObject()
	{
		$this->object->setSchema([
			'array' => ''
		]);
		$this->object->setValue(['foo', 'bar', 'boom']);

		$itemCount = \count($this->object);

		$this->assertEquals(3, $itemCount);
	}

	public function testItemsAreTraversableThroughObject()
	{
		$this->object->setSchema([
			'array' => ''
		]);
		$this->object->setValue(['foo', 'bar', 'boom']);

		$loopCount = 0;
		foreach( $this->object as $field )
		{
			$loopCount++;
		}

		$this->assertEquals(3, $loopCount);
	}

	public function testItemsAreNamedAsArrayElementsIfArrayHasName()
	{
		$this->object->setName('children');
		$this->object->setSchema([
			'array' => ''
		]);
		$this->object->setValue(['foo', 'bar']);

		$this->assertEquals('children[0]', $this->object[0]->getName());
		$this->assertEquals('children[1]', $this->object[1]->getName());
	}

	public function testAddItem()
	{
		$this->object->setSchema([
			'array' => ''
		]);

		$this->object->addItem('foo');
		$this->object->addItem('bar');

		$this->assertEquals(['foo', 'bar'], $this->object->getValue());
	}

	public function testAddItemReturnsNewItemKey()
	{
		$this->object->setSchema([
			'array' => ''
		]);

		$keyFoo = $this->object->addItem('foo');
		$keyBar = $this->object->addItem('bar');

		$this->assertEquals(0, $keyFoo);
		$this->assertEquals(1, $keyBar);
	}
}

