<?php
namespace XAF\web\routing;

use PHPUnit\Framework\TestCase;

class BackrefReplacerTest extends TestCase
{
	public function testMissingMatchReplacesEmptyString()
	{
		$replacer = new BackrefReplacer([]);

		$result = $replacer->process('x$1');

		// $<digit> expressions for which there is no replacement shall be replaced by an empty string
		$this->assertEquals('x', $result);
	}

	public function testNormalReplacement()
	{
		$replacer = new BackrefReplacer(['foo', 'bar']);

		$result = $replacer->process('$0-$1');

		$this->assertEquals('foo-bar', $result);
	}

	public function testUcFirstReplacement()
	{
		$replacer = new BackrefReplacer(['foo']);

		$result = $replacer->process('get$u0');

		$this->assertEquals('getFoo', $result);
	}

	public function testDollarSignEscaping()
	{
		$replacer = new BackrefReplacer(['foo', 'bar']);

		// '$$' is the escape sequence for a literal '$'
		$result = $replacer->process('$$$1$$');

		$this->assertEquals('$bar$', $result);
	}

}
