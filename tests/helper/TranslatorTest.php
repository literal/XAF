<?php
namespace XAF\helper;

use PHPUnit\Framework\TestCase;

// @todo add tests for multiple table paths/path fallback
/**
 * @covers \XAF\helper\Translator
 */
class TranslatorTest extends TestCase
{
	/** @var Translator */
	private $object;

	protected function setUp(): void
	{
		$this->object = new Translator(__DIR__ . '/test_messages');
	}

	public function testLookupByLanguage()
	{
		$result = $this->object->translate('language', 'message_table', 'de');

		$this->assertEquals('Deutsch', $result);
	}

	public function testLookupByFullLanguageTag()
	{
		$result = $this->object->translate('language', 'message_table', 'de-ch');

		$this->assertEquals('Deutsch, Schweiz', $result);
	}

	public function testFallbackForPartialLanguageMatch()
	{
		$result = $this->object->translate('language', 'message_table', 'de-at');

		$this->assertEquals('Deutsch', $result);
	}

	public function testFallbackIfNoLanguageMatch()
	{
		$result = $this->object->translate('language', 'message_table', 'fr-fr');

		$this->assertEquals('Universal', $result);
	}

	public function testParamReplacement()
	{
		$result = $this->object->translate('parametrized', 'message_table', 'de', ['foo' => 'bar']);

		$this->assertEquals('the value of param "foo" is bar.', $result);
	}

	public function testLiteralPercentSignCanBeEscapedByDoublePercentSign()
	{
		$result = $this->object->translate('with_colon_escape', 'message_table', 'de');

		$this->assertEquals('a percent sign %foo', $result);
	}

	public function testCallableTableEntry()
	{
		$result = $this->object->translate('callable', 'message_table', 'de', ['foo' => 'bar']);

		$this->assertEquals('the value of param "foo" is bar.', $result);
	}

	public function testMessageKeyIsUsedAsPatternWhenMessageTableIsMissing()
	{
		$result = $this->object->translate('messageKey', 'non_existent_table', null);

		$this->assertEquals('messageKey', $result);
	}

	public function testParamsAreExpandedInKeyIfMessageTableIsMissing()
	{
		$result = $this->object->translate('messageKey %foo%', 'non_existent_table', null, ['foo' => 'FOO']);

		$this->assertEquals('messageKey FOO', $result);
	}

	// Border conditions

	public function testNonArrayReturningMessageFileTriggersWarning()
	{
		$this->setExpectedPhpUserWarning('Message table file does not return an array');
		$this->object->translate('messageKey', 'malformed_table', null);
	}

	public function testInvalidTableEntryTriggersWarning()
	{
		$this->setExpectedPhpUserWarning('Invalid entry for key');
		$this->object->translate('invalid', 'message_table', 'de-de');
	}

	public function testUndefinedMessageKeyTriggersWarning()
	{
		$this->setExpectedPhpUserWarning('Unknown message key');
		$this->object->translate('undefinedKey', 'message_table', 'de');
	}

	private function setExpectedPhpUserWarning( $message )
	{
		$this->expectError();
		$this->expectErrorMessage($message);
	}
}
