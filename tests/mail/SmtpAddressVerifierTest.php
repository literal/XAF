<?php
namespace XAF\mail;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\mail\SmtpAddressVerifier
 */
class SmtpAddressVerifierTest extends TestCase
{
	/** @var SmtpAddressVerifier */
	private $object;

	protected function setUp(): void
	{
		$this->object = new SmtpAddressVerifier();
	}

	public function testExistingAddressIsValid()
	{
		$this->assertTrue($this->object->isValid('contact@digitalstores.net'));
	}

	public function testAddressWithExistentIdnDomainIsValid()
	{
		$this->assertTrue($this->object->isValid('support@höbu.de'));
	}

	public function testMalformedAddressIsInvalid()
	{
		$this->assertFalse($this->object->isValid('foo@bar@google.com'));
	}

	public function testAddressWithNonExistentDomainIsInvalid()
	{
		$this->assertFalse($this->object->isValid('contact@ase79gsf087hsdf987twfo8uhsdf.com'));
	}

	public function testAddressWithExistentDomainButWithoutReachableSmtpServerIsInvalid()
	{
		$this->assertFalse($this->object->isValid('contact@secure.digitalstores.net'));
	}
}
