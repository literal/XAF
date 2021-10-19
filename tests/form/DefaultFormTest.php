<?php
namespace XAF\form;

use PHPUnit\Framework\TestCase;

use XAF\validate\ValidationServiceStub;

require_once __DIR__ . '/stubs/ValidationServiceStub.php';

/**
 * @covers \XAF\form\DefaultForm
 */
class DefaultFormTest extends TestCase
{
	/** @var DefaultForm */
	protected $object;

	/** @var ValidationServiceStub */
	private $validationServiceStub;

	protected function setUp(): void
	{
		$this->validationServiceStub = new ValidationServiceStub();
		$this->object = new DefaultForm($this->validationServiceStub);
	}

	public function testDefinedFieldCanBeAccessed()
	{
		$this->object->setSchema([
			'key' => []
		]);

		$this->assertNull($this->object['key']->getValue());
	}

	public function testAccessingUndefinedFieldThrowsException()
	{
		$this->expectException(\XAF\exception\SystemError::class);
		$this->object['key']->getValue();
	}

	// ************************************************************************
	// Assignment of values
	// ************************************************************************

	public function testPopulateWithDefaultsSetsFieldValues()
	{
		$this->object->setSchema([
			'key' => ['default' => 'value']
		]);

		$this->object->populateWithDefaults();

		$this->assertEquals('value', $this->object['key']->getValue());
	}

	public function testImportSetsFieldValues()
	{
		$this->object->setSchema([
			'key' => []
		]);

		$this->object->importValues(['key' => 'value']);

		$this->assertEquals('value', $this->object['key']->getValue());
	}

	public function testFieldObjectCannotBeSetFromTheOutside()
	{
		$this->object->setSchema([
			'key' => []
		]);

		$this->expectException(\XAF\exception\SystemError::class);
		$this->object['key'] = new FormField($this->validationServiceStub);
	}

	public function testFieldObjectCannotBeUnsetFromTheOutside()
	{
		$this->object->setSchema([
			'key' => []
		]);

		$this->expectException(\XAF\exception\SystemError::class);
		unset($this->object['key']);
	}

	// ************************************************************************
	// Retrieval of values
	// ************************************************************************

	public function testExportReturnsFieldValuesAsHash()
	{
		$this->object->setSchema([
			'key' => []
		]);
		$this->object['key']->setValue('value');

		$result = $this->object->exportValues();

		$this->assertEquals(['key' => 'value'], $result);
	}

	// ************************************************************************
	// Field types
	// ************************************************************************

	public function testDefaultFieldTypeIsFormField()
	{
		$this->object->setSchema([
			'key' => []
		]);

		$this->assertInstanceOf('XAF\\form\\FormField', $this->object['key']);
	}

	public function testNoFieldTypeCreatesField()
	{
		$this->object->setSchema([
			'key' => []
		]);

		$this->assertInstanceOf('XAF\\form\\FormField', $this->object['key']);
	}

	public function testStructSchemaElementCreatesStruct()
	{
		$this->object->setSchema([
			'key' => [
				'struct' => []
			]
		]);

		$this->assertInstanceOf('XAF\\form\\FormItemStruct', $this->object['key']);
	}

	public function testHashSchemaElementCreatesHash()
	{
		$this->object->setSchema([
			'key' => [
				'hash' => []
			]
		]);

		$this->assertInstanceOf('XAF\\form\\FormItemHash', $this->object['key']);
	}

	public function testArraySchemaElementCreatesArray()
	{
		$this->object->setSchema([
			'key' => [
				'array' => []
			]
		]);

		$this->assertInstanceOf('XAF\\form\\FormItemArray', $this->object['key']);
	}

	// ************************************************************************
	// Validation
	// ************************************************************************

	public function testValidationRuleIsAppliedIfRUleElementExists()
	{
		$this->object->setSchema([
			'key' => ['rule' => 'pass']
		]);

		$this->object->validate();

		$this->assertEquals(1, $this->validationServiceStub->callCount);
	}

	public function testStringAsDefinitionIsTreatedAsValidationRule()
	{
		$this->object->setSchema([
			'key' => 'pass'
		]);

		$this->object->validate();

		$this->assertEquals(1, $this->validationServiceStub->callCount);
	}

	public function testValuesAreNotValidatedIfNoRuleExists()
	{
		$this->object->setSchema([
			'key' => []
		]);

		$this->object->validate();

		$this->assertEquals(0, $this->validationServiceStub->callCount);
	}

	public function testStringFieldDefinitionIsTreatedAsValidationRule()
	{
		$this->object->setSchema([
			'key' => ['rule' => 'pass']
		]);

		$this->object->validate();

		$this->assertEquals(1, $this->validationServiceStub->callCount);
	}

	public function testFormInitiallyReportsNoError()
	{
		$this->assertFalse($this->object->hasError());
	}

	public function testValidationFailureSetsFieldError()
	{
		$this->object->setSchema([
			'key' => ['rule' => 'fail']
		]);

		$this->object->validate();

		$this->assertTrue($this->object['key']->hasError());
	}

	public function testFieldErrorSetsWholeFormIntoErrorState()
	{
		$this->object->setSchema([
			'key' => ['rule' => 'fail']
		]);

		$this->object->validate();

		$this->assertTrue($this->object->hasError());
	}

	public function testFieldErrorIsNotReportedAsGlobalError()
	{
		$this->object->setSchema([
			'foo' => ['rule' => 'fail']
		]);

		$this->object->validate();

		$this->assertFalse($this->object->hasGlobalError());
	}

	public function testErrorSetOnFormObjectSetsFormIntoErrorState()
	{
		$this->object->setError('something is wrong');

		$this->assertTrue($this->object->hasError());
	}

	public function testErrorSetOnFormObjectIsGlobalError()
	{
		$this->object->setError('something is wrong');

		$this->assertTrue($this->object->hasGlobalError());
	}

	public function testGetErrorReturnsErrorKeyAndErrorInfo()
	{
		$this->object->setError('something is wrong', ['value' => 'foo']);

		$this->assertEquals(
			[
				'key' => 'something is wrong',
				'info' => ['value' => 'foo']
			],
			$this->object->getError()
		);
	}

	public function testGetErrorKeyReturnsErrorKey()
	{
		$this->object->setError('something is wrong', ['value' => 'foo']);

		$this->assertEquals('something is wrong', $this->object->getErrorKey());
	}

	public function testGetErrorInfoReturnsErrorInfo()
	{
		$this->object->setError('something is wrong', ['value' => 'foo']);

		$this->assertEquals(['value' => 'foo'], $this->object->getErrorInfo());
	}

	// ************************************************************************
	// Global errors
	// ************************************************************************

	public function testSetGlobalErrorSetsErrorState()
	{
		$this->object->setGlobalError('someErrorKey');

		$this->assertTrue($this->object->hasError());
		$this->assertTrue($this->object->hasGlobalError());
		$this->assertEquals('someErrorKey', $this->object->getErrorKey());
	}

	// ************************************************************************
	// 'Received' flag
	// ************************************************************************

	public function testReceivedFlagIsFalseByDefault()
	{
		$this->assertFalse($this->object->wasReceived());
	}

	public function testReceivedFlagCanBeSet()
	{
		$this->object->setReceived();

		$this->assertTrue($this->object->wasReceived());
	}
}
