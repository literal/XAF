<?php
namespace XAF\form;

use PHPUnit\Framework\TestCase;

use XAF\validate\ValidationServiceStub;

require_once __DIR__ . '/stubs/ValidationServiceStub.php';

/**
 * @covers \XAF\form\FormItemHash
 */
class FormItemHashTest extends TestCase
{
	/** @var FormItemHash */
	protected $object;

	/** @var ValidationServiceStub */
	private $validationServiceStub;

	protected function setUp(): void
	{
		$this->validationServiceStub = new ValidationServiceStub();
		$this->object = new FormItemHash($this->validationServiceStub);
	}

	public function testEachElementInDefaultsHashYieldsAnItem()
	{
		$this->object->setSchema([
			'hash' => '',
			'default' => ['f' => 'foo', 'b' => 'bar']
		]);

		$this->object->setDefault();

		$this->assertEquals('foo', $this->object['f']->getValue());
		$this->assertEquals('bar', $this->object['b']->getValue());
	}

	public function testItemsAreCreatedBySetValue()
	{
		$this->object->setSchema(['hash' => '']);

		$this->object->setValue(['f' => 'foo', 'b' => 'bar']);

		$this->assertEquals('foo', $this->object['f']->getValue());
		$this->assertEquals('bar', $this->object['b']->getValue());
	}

	public function testThereAreNoItemsAfterSettingEmptyValue()
	{
		$this->object->setSchema(['hash' => '']);

		$this->object->setValue(['f' => 'foo']);
		$this->object->setValue(null);

		$this->assertCount(0, $this->object);
	}

	public function testThereAreNoItemsAfterSettingScalarValue()
	{
		$this->object->setSchema(['hash' => '']);

		$this->object->setValue(['f' => 'foo']);
		$this->object->setValue('foo');

		$this->assertCount(0, $this->object);
	}

	public function testMissingHashSchemaElementThrowsException()
	{
		$this->expectException(\XAF\exception\SystemError::class);
		$this->object->setSchema([]);
	}

	public function testScalarDefaultThrowsException()
	{
		$this->object->setSchema([
			'hash' => '',
			'default' => 'foo'
		]);

		$this->expectException(\XAF\exception\SystemError::class);
		$this->object->setDefault();
	}

	public function testNoItemsAreCreatedWithoutDefault()
	{
		$this->object->setSchema(['hash' => '']);

		$this->object->setDefault();

		$this->assertCount(0, $this->object);
	}

	public function testElementsAreValidated()
	{
		$this->object->setSchema([
			'hash' => [
				'rule' => 'fail'
			]
		]);
		// There is nothing to validate if no fields are present - and only
		// setting defaults or values will actually create fields,
		// so we set one value
		$this->object->setValue(['f' => 'foo']);

		$this->object->validate();

		$this->assertTrue($this->object->hasError());
	}

	public function testItemsAreCountableThroughObject()
	{
		$this->object->setSchema(['hash' => '']);
		$this->object->setValue(['f' => 'foo', 'b' => 'bar', 'o' => 'boom']);

		$itemCount = \count($this->object);

		$this->assertEquals(3, $itemCount);
	}

	public function testItemsAreTraversableThroughObject()
	{
		$this->object->setSchema(['hash' => '']);
		$this->object->setValue(['f' => 'foo', 'b' => 'bar', 'o' => 'boom']);

		$loopCount = 0;
		foreach( $this->object as $field )
		{
			$loopCount++;
		}

		$this->assertEquals(3, $loopCount);
	}

	public function testItemsAreNamedAsHashElementsIfHashHasName()
	{
		$this->object->setName('children');
		$this->object->setSchema(['hash' => '']);
		$this->object->setValue(['f' => 'foo', 'b' => 'bar']);

		$this->assertEquals('children[f]', $this->object['f']->getName());
		$this->assertEquals('children[b]', $this->object['b']->getName());
	}

	public function testSetItem()
	{
		$this->object->setSchema(['hash' => '']);

		$this->object->setItem('f', 'foo');
		$this->object->setItem('b', 'bar');

		$this->assertEquals(['f' => 'foo', 'b' => 'bar'], $this->object->getValue());
	}

	public function testSetTemplateCreatesItem()
	{
		$this->object->setSchema(['hash' => '']);

		$this->object->setTemplate(['foo' => true]);

		$this->assertEquals(['foo' => true], $this->object->getValue());
	}

	public function testSetTemplateSetsItemOrder()
	{
		$this->object->setSchema(['hash' => '']);
		$this->object->setValue(['bar' => true, 'foo' => true]);

		$this->object->setTemplate(['bom' => false, 'foo' => false]);

		// 'bar' should be appended because it is present in the original value but not part of the template
		$this->assertSame(['bom', 'foo', 'bar'], \array_keys($this->object->getValue()));
	}

	public function testSetTemplateKeepsExistingItem()
	{
		$this->object->setSchema(['hash' => '']);
		$this->object->setValue(['foo' => 'foo']);
		$this->object['foo']->setParam('option', 'bar');
		$this->object['foo']->setError('invalid');

		$this->object->setTemplate(['foo' => 'default']);

		$this->assertEquals('foo', $this->object['foo']->getValue());
		$this->assertEquals('bar', $this->object['foo']->getParam('option'));
		$this->assertEquals('invalid', $this->object['foo']->getErrorKey());
	}
}
