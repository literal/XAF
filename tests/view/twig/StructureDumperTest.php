<?php
namespace XAF\view\twig;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\view\twig\StructureDumper
 */
class StructureDumperTest extends TestCase
{
	/** @var StructureDumper */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new StructureDumper();
	}

	static public function getDumpTestTuples()
	{
		return [
			['foobar', '"foobar"'],
			[821, '821'],
			[123.456, '123.456'],
			[true, 'true'],
			[false, 'false'],
			[null, 'none'],

			// empty arrays and objects shall yield 'empty'
			[[], 'empty'],
			[new \stdClass, 'empty']
		];
	}

	/**
	 * @dataProvider getDumpTestTuples
	 */
	public function testSimpleDumps( $value, $expectedOutput )
	{
		$result = $this->object->dump($value);

		$this->assertSame($expectedOutput, $result);
	}

	public function testDumpHash()
	{
		$value = [
			'one' => 1,
			'two' => 2
		];

		$result = $this->object->dump($value);

		$this->assertEquals(
			"one: 1\n" .
			'two: 2',
			$result
		);
	}

	public function testDumpArray()
	{
		$value = [1, 2];

		$result = $this->object->dump($value);

		$this->assertEquals(
			"0: 1\n" .
			'1: 2',
			$result
		);
	}

	public function testDumpObject()
	{
		$value = new DumpTestClass;

		$result = $this->object->dump($value);

		$this->assertEquals(
			"public: \"public\"\n" .
			"staticMethod()\n" .
			"publicMethod()\n" .
			'publicMethodWithParameters( array, string [, optional] )',
			$result
		);
	}

	public function testDumpNestedStructure()
	{
		$value = [
			'level1' => [
				'level2' => [
					'level3' => 'foo'
				]
			]
		];

		$result = $this->object->dump($value);

		$this->assertEquals(
			"level1: \n" .
			"    level2: \n" .
			'        level3: "foo"',
			$result
		);
	}

	public function testMaxNestingLevelIsObserved()
	{
		$value = [
			'level1' => [
				'level2' => [
					'level3' => 'foo'
				]
			]
		];

		$this->object->setMaxNestingLevel(2);
		$result = $this->object->dump($value);

		$this->assertEquals(
			"level1: \n" .
			'    level2: ...',
			$result
		);
	}
}


class DumpTestClass
{
	private $private = 'private';
	protected $protected = 'protected';
	static public $static = 'static';
	public $public = 'public';

	public function __construct() {}
	private function privateMethod() {}
	protected function protectedMethod() {}
	static public function staticMethod() {}
	public function publicMethod() {}
	public function publicMethodWithParameters( array $array, $string, $optional = null ) {}
}
