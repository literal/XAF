<?php
namespace XAF\config;

use PHPUnit\Framework\TestCase;
use XAF\test\PhpUnitTestHelper;

/**
 * @covers \XAF\config\ConfigBuilder
 */
class ConfigBuilderTest extends TestCase
{
	/** @var ConfigBuilder */
	private $object;

	/** @var Config */
	private $config;

	/** @var string */
	private $testFilePath;

	protected function setUp(): void
	{
		$this->testFilePath = __DIR__ . '/testconfigurations';
		$this->config = new DefaultConfig();
		$this->object = new ConfigBuilder($this->config);
	}

	function testLoadConfigFile()
	{
		$this->object->loadConfigFile(null, $this->testFilePath . '/foo.php');

		$this->assertEquals('bar', $this->config->get('foo.foo'));
	}

	function testMergeConfigFile()
	{
		$this->object->loadConfigFile(null, $this->testFilePath . '/foo.php');
		$this->object->mergeConfigFile(null, $this->testFilePath . '/bar.php');

		$this->assertEquals('bar', $this->config->get('foo.bar'));
	}

	function testMissingFile()
	{
		$this->expectException(\XAF\config\ConfigBuilderError::class);
		$this->expectExceptionMessage('failed to include config file');
		@$this->object->loadConfigFile(null, $this->testFilePath . '/missing.php');
	}

	function testEmptyFile()
	{
		$this->expectException(\XAF\config\ConfigBuilderError::class);
		$this->expectExceptionMessage('failed to include config file');
		$this->object->loadConfigFile(null, $this->testFilePath . '/empty.php');
	}

	function testStringFile()
	{
		$this->expectException(\XAF\config\ConfigBuilderError::class);
		$this->expectExceptionMessage('included config file did not return an array');
		$this->object->loadConfigFile(null, $this->testFilePath . '/string.php');
	}

}
