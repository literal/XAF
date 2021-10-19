<?php
namespace XAF\config;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\config\DefaultConfig
 */
class DefaultConfigTest extends TestCase
{
	/** @var DefaultConfig */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new DefaultConfig;
	}

	public function testGetUndefinedOption()
	{
		$result = $this->object->get('undefined.undefined');

		$this->assertNull($result);
	}

	public function testGetUndefinedRequiredOption()
	{
		$this->expectException(\XAF\exception\SystemError::class);
		$this->object->getRequired('undefined.undefined');
	}

	public function testSet()
	{
		$this->object->set('main.sub', 'value');

		$this->assertEquals('value', $this->object->get('main.sub'));
	}

	public function testExport()
	{
		$this->object->import(null, ['main' => ['sub' => ['leaf' => ['key' => 'value']]]]);

		$result = $this->object->export('main.sub');

		$this->assertInstanceOf('XAF\\config\\Config', $result);
		$this->assertEquals('value', $result->get('leaf.key'));
	}

	/**
	 * Only subtrees can be exported, not single values
	 */
	public function testExportOfScalarValue()
	{
		$this->object->import(null, ['key' => 'value']);

		$this->expectException(\XAF\exception\SystemError::class);
		$this->object->export('key');
	}

	public function testImportRootAndGetKey()
	{
		// null-key means options shall be set at the root of the config data
		$this->object->import(null, ['option' => 'value']);

		$this->assertEquals('value', $this->object->get('option'));
	}

	public function testImportRootAndGetRequiredKey()
	{
		// null-key means options shall be set at the root of the config data
		$this->object->import(null, ['option' => 'value']);

		$this->assertEquals('value', $this->object->getRequired('option'));
	}

	public function testImportRootAndGetRoot()
	{
		// null-key means options shall be set at the root of the config data
		$this->object->import(null, 'value');

		$this->assertEquals('value', $this->object->get(null));
	}

	public function testImportedNestedArrayFieldsAccessibleInDotNotation()
	{
		$this->object->import('option', ['sub' => 'value']);

		$this->assertEquals('value', $this->object->get('option.sub'));
	}

	public function testDeepSubKeyImport()
	{
		$this->object->import('option.sub', ['sub' => 'value']);

		$this->assertEquals('value', $this->object->get('option.sub.sub'));
	}

	public function testImportOverwritesEverythingBeneathKey()
	{
		$this->object->import('option', ['sub1' => 'value1']);

		$this->object->import('option', ['sub2' => 'value2']); // importing 'option' again should delete 'option.sub1'

		$this->assertNull($this->object->get('option.sub1'));
	}

	public function testImportDoesNotOverwriteUnrelatedKey()
	{
		$this->object->import('option1', 'value1');

		$this->object->import('option2', 'value2'); // Shall not touch 'option1'

		$this->assertEquals('value1', $this->object->get('option1'));
	}

	public function testMergeImportsNewKey()
	{
		$this->object->mergeBranch('option', 'value');

		$this->assertEquals('value', $this->object->get('option'));
	}

	public function testElementIsEmptyAfterRemove()
	{
		$this->object->set('option', 'value');
		$this->object->remove('option');

		$this->assertEmpty($this->object->get('option'));
	}

	public function testMergeOverwritesExistingScalarValue()
	{
		$this->object->import('option', 'value');

		$this->object->mergeBranch('option', 'new_value');

		$this->assertEquals('new_value', $this->object->get('option'));
	}

	public function testMergeDoesMerge()
	{
		$this->object->import('option', ['sub1' => 'value1']);

		// merging 'option' should keep 'option.sub1' and add 'option.sub2'
		$this->object->mergeBranch('option', ['sub2' => 'value2']);

		$this->assertEquals('value1', $this->object->get('option.sub1'));
		$this->assertEquals('value2', $this->object->get('option.sub2'));
	}

	public function testMergeKeepsExistingElement()
	{
		$this->object->import('option.sub', ['key1' => 'value1']);

		// merging 'option' should keep 'option.sub.key1' and add 'option.sub.key2'
		$this->object->mergeBranch('option', ['sub' => ['key2' => 'value2']]);

		$this->assertEquals('value1', $this->object->get('option.sub.key1'));
		$this->assertEquals('value2', $this->object->get('option.sub.key2'));
	}

	public function testMergeReplacesScalarArrayElementsByKey()
	{
		$this->object->import('option', ['value1', 'value2']);

		// merging 'option' should combine both values into a scalar array
		$this->object->mergeBranch('option', ['value1new']);

		$this->assertEquals(['value1new', 'value2'], $this->object->get('option'));
	}

	public function testMergeReplacesSubtreeWithScalar()
	{
		$this->object->import('option', ['sub' => ['key' => 'value']]);

		// merge should replace hashmap in 'option.sub2' by string
		$this->object->mergeBranch('option', ['sub' => 'value']);

		$this->assertNull($this->object->get('option.sub.key'));
	}


	public function testMergeOverwritesScalarsAlongTheWay()
	{
		$this->object->import('option', ['sub' => 'value']);

		// merge should replace hashmap in 'option.sub' by array
		$this->object->mergeBranch('option', ['sub' => ['key' => 'value']]);

		$this->assertIsArray($this->object->get('option.sub'));
	}
}
