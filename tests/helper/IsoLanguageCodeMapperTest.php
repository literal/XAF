<?php
namespace XAF\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\helper\IsoLanguageCodeMapper
 */
class IsoLanguageCodeMapperTest extends TestCase
{
	public function testAlpha3ToAlpha2()
	{
		$this->assertEquals('es', IsoLanguageCodeMapper::alpha3ToAlpha2('spa'));
	}

	public function testAlpha3ToAlpha2ReturnsNullWhenNoMappingExists()
	{
		$this->assertNull(IsoLanguageCodeMapper::alpha3ToAlpha2('xxx'));
	}

	public function testAlpha2ToAlpha3()
	{
		$this->assertEquals('spa', IsoLanguageCodeMapper::alpha2ToAlpha3('es'));
	}

	public function testAlpha2ToAlpha3ReturnsNullWhenNoMappingExists()
	{
		$this->assertNull(IsoLanguageCodeMapper::alpha2ToAlpha3('xx'));
	}
}

