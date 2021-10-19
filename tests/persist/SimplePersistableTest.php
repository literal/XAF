<?php
namespace XAF\persist;

use PHPUnit\Framework\TestCase;

require __DIR__ . '/stub/PersistableStub.php';

/**
 * @covers \XAF\persist\SimplePersistable
 */
class SimplePersistableTest extends TestCase
{
	/** @var SimplePersistable */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new PersistableStub();
	}

	public function testImportState()
	{
		$this->object->foo = 'overwriteme';

		$this->object->importState(['foo' => 'foo']);

		$this->assertEquals('foo', $this->object->foo);
	}

	public function testExportState()
	{
		$this->object->foo = 'exportme';

		$data = $this->object->exportState();

		$this->assertEquals('exportme', $data['foo']);
	}

}
