<?php
namespace XAF\form;

use PHPUnit\Framework\TestCase;

use XAF\validate\ValidationServiceStub;

require_once __DIR__ . '/stubs/ValidationServiceStub.php';

/**
 * Most of FormField is tested through DefaultFormTest
 *
 * @covers \XAF\form\FormField
 */
class FormFieldTest extends TestCase
{
	/** @var FormField */
	protected $object;

	/** @var ValidationServiceStub */
	private $validationServiceStub;

	protected function setUp(): void
	{
		$this->validationServiceStub = new ValidationServiceStub();
		$this->object = new FormField($this->validationServiceStub);
	}

	public function testValueIsTrimmedBySuccessfulValidationByDefault()
	{
		$this->object->setSchema(['rule' => 'pass']);
		$this->object->setValue(' foo ');

		$this->object->validate();

		$this->assertEquals('foo', $this->object->getValue());
	}

	public function testValueIsNotTrimmedBySuccessfulValidationIfTrimFlagIsFalse()
	{
		$this->object->setSchema(['rule' => 'fail', 'trim' => false]);
		$this->object->setValue(' foo ');

		$this->object->validate();

		$this->assertEquals(' foo ', $this->object->getValue());
	}

	public function testValueIsNotTrimmedIfValidationFails()
	{
		$this->object->setSchema(['rule' => 'fail']);
		$this->object->setValue(' foo ');

		$this->object->validate();

		$this->assertEquals(' foo ', $this->object->getValue());
	}

	public function testCastingFormFieldToStringYieldsValue()
	{
		$this->object->setValue('foo');

		$value = \strval($this->object);

		$this->assertEquals('foo', $value);
	}

	public function testGetIdReturnsNameWithNonIdCharactersReplacedWithUnderscores()
	{
		$this->object->setName('foo[bar]');

		$this->assertEquals('foo_bar', $this->object->getId());
	}

	/**
	 * Displays the common case that selection options are stored in a form field
	 * to be used by the presentation layer
	 */
	public function testParamsCanBeStored()
	{
		$options = ['x' => 'x-ray', 'y' => 'yankee', 'z' => 'zulu'];

		$this->object->setParam('options', $options);

		$this->assertEquals($options, $this->object->getParam('options'));
	}
}
