<?php
namespace XAF\validate;

use XAF\view\twig\Environment;
use Twig_Loader_String;

require_once 'ValidationTestBase.php';

/**
 * @covers \XAF\validate\TwigStringValidator
 */
class TwigStringValidatorTest extends ValidationTestBase
{
	/** @var TwigStringValidator */
	protected $object;

	protected function setUp(): void
	{
		$loader = new Twig_Loader_String();
		$twigEnv = new Environment($loader, ['autoescape' => false]);

		$this->object = new TwigStringValidator($twigEnv);
	}

	public function testValidTwigString()
	{
		$result = $this->object->validate('{{ track.number }} - {{ track.name }}.mp3');

		$this->assertEquals('{{ track.number }} - {{ track.name }}.mp3', $result->value);
	}

	public function testEmptyStatementDoesNotValidate()
	{
		$result = $this->object->validate('');

		$this->assertValidationError('empty', $result);
	}

	public function testMissingEndOfPrintStatementDoesNotValidate()
	{
		$result = $this->object->validate('{{ track.name');

		$this->assertValidationError('invalidTwigSyntax', $result);
	}

}
