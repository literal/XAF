<?php
namespace XAF\form;

use PHPUnit\Framework\TestCase;

use XAF\validate\ValidationServiceStub;

require_once __DIR__ . '/stubs/ValidationServiceStub.php';

/**
 * This test demonstrates the common use case of a form for an
 * item which has a variable number of attached sub-items, which in
 * turn have a set of named properties
 *
 * @covers \XAF\form\DefaultForm
 */
class ComplexFormTest extends TestCase
{
	/** @var DefaultForm */
	protected $object;

	/** @var ValidationServiceStub */
	private $validationServiceStub;

	protected function setUp(): void
	{
		$this->validationServiceStub = new ValidationServiceStub();
		$this->object = new DefaultForm($this->validationServiceStub);

		$this->object->setSchema([
				// 'pass' is just a validator stub expression for an always successful validation
			'id' => 'pass',
			'name' => 'pass',
				// In the field named 'children'...
			'children' => [
					// ...we have an array...
				'array' => [
						// ...of structs...
					'struct' => [
							// ...each of which has an 'id'...
						'id' => 'pass',
							// ...and a 'name' field
						'name' => 'pass'
					]
				],
					// a default child
				'default' => [
					['id' => '0', 'name' => 'default']
				]
			]
		]);

	}

	public function testValuesCanBeImportedAsNestedArray()
	{
		$this->object->importValues([
			'id' => 1,
			'name' => 'Borst',
			'children' => [
				['id' => 2, 'name' => 'Foo'],
				['id' => 3, 'name' => 'Bar']
			]
		]);

		$this->assertEquals(1, $this->object['id']->getValue());
		$this->assertEquals('Bar', $this->object['children'][1]['name']->getValue());
	}

	public function testFieldNamesReflectNesting()
	{
		// we need at least one child
		$this->object->importValues(['children' => [[]]]);

		$this->assertEquals('id', $this->object['id']->getName());
		$this->assertEquals('children[0][id]', $this->object['children'][0]['id']->getName());
	}

	public function testChildrenCanBeTraversedWithForeach()
	{
		$this->object->importValues([
			'id' => 1,
			'name' => 'Borst',
			'children' => [
				['id' => 2, 'name' => 'Foo'],
				['id' => 3, 'name' => 'Bar']
			]
		]);

		$expectedNames = ['Foo', 'Bar'];
		$expectedNameIndex = 0;
		foreach( $this->object['children'] as $child )
		{
			$this->assertEquals(
				$expectedNames[$expectedNameIndex],
				$child['name']->getValue()
			);
			$expectedNameIndex++;
		}
	}

	public function testValidationReachesAllFields()
	{
		$this->object->importValues([
			'id' => 1,
			'name' => 'Borst',
			'children' => [
				['id' => 2, 'name' => 'Foo'],
				['id' => 3, 'name' => 'Bar']
			]
		]);

		$this->object->validate();

		// 3 IDs and 3 names should have been validated
		$this->assertEquals(6, $this->validationServiceStub->callCount);
	}

	public function testDefaultChildIsCreated()
	{
		$this->object->populateWithDefaults();

		// 3 IDs and 3 names should have been validated
		$this->assertCount(1, $this->object['children']);
	}

	public function testImportCanBePartial()
	{
		$this->object->importValues([
			'id' => 8,
			'children' => [
				['name' => 'Foo'],
				['id' => 2],
				[]
			]
		]);

		$this->assertCount(3, $this->object['children']);
	}

	public function testExportContainsAllFields()
	{
		$this->object->importValues([
			'children' => [
				[]
			]
		]);

		$result = $this->object->exportValues();

		$this->assertEquals(
			[
				'id' => null,
				'name' => null,
				'children' => [
					['id' => null, 'name' => null]
				]
			],
			$result
		);
	}
}
