<?php
namespace XAF\helper;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\helper\ShellCommandRunner
 */
class ShellCommandRunnerTest extends TestCase
{
	/** @var ShellCommandRunner */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new ShellCommandRunner();
	}

	public function testExecuteWithNonZeroReturnCodeThrowsException()
	{
		$this->expectException(\XAF\helper\ShellCommandException::class);
		$this->object->execute(['php', __DIR__ . '/test_commands/return_arg_as_rc.php', 1]);
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testExecuteRawWithNonZeroReturnCodeDoesNotThrowException()
	{
		$this->object->executeRaw(['php', __DIR__ . '/test_commands/return_arg_as_rc.php', 1]);
	}

	public function testExecuteRawReturnsShellReturnCode()
	{
		$result = $this->object->executeRaw(['php', __DIR__ . '/test_commands/return_arg_as_rc.php', 167]);

		$this->assertEquals(167, $result['returnCode']);
	}

	public function testExecuteRawReturnsCommandLine()
	{
		$result = $this->object->executeRaw(['foo', 'bar']);

		$this->assertEquals(
			$this->isRunningOnWindows() ? '"foo" "bar" 2>&1' : '\'foo\' \'bar\' 2>&1',
			$result['commandLine']
		);
	}

	public static function getTestArgTuples()
	{
		return [
			[['"Foo" "Bar"']],
			[["'Foo' 'Bar'"]],
			[['F"o"o', 'B"a"r']],
			[["F'o'o", "B'a'r"]],
			[['\\$\'#&;`|*?~<>^()[]{}']],
			[['  x']], // leading whitespace is preserved

			// These will not work on Windows: Windows leaves the unescaping to the application and even passes
			// the surrounding double quotes of an arg. As the application called in this test is a PHP script,
			// this only reflects how PHP parses command line arguments in CLI mode on Windows

			// Trailing whitespace shall be preserved
			[['x  '], true],

			// Trailing backslash - this will become "x\" on the Windows command line and PHP will consider the last
			// two characters an escape sequence for " so x" is echoed back. While this *could* be mitigated by
			// doubling the trailing backslash, this only works at the very end, not in the middle of the string.
			// And as again this only demonstrates how PHP CLI is handling arguments, there is no fix in
			// ShellCommandRunner.
			[['x\\'], true],


		];
	}

	/**
	 * @dataProvider getTestArgTuples
	 */
	public function testExecutePassesArgsCorrectly( array $args, $wontWorkOnWindows = false )
	{
		if( $wontWorkOnWindows )
		{
			$this->skipTestIfRunningOnWindows();
		}

		$commandParts = \array_merge(['php', __DIR__ . '/test_commands/echo_args_as_json.php'], $args);

		$commandOutput = $this->object->execute($commandParts);

		$this->assertEquals($args, $this->decodeEchoedArgs($commandOutput));
	}

	/**
	 * @dataProvider getTestArgTuples
	 */
	public function testExecuteRawPassesArgsCorrectly( array $args, $wontWorkOnWindows = false )
	{
		if( $wontWorkOnWindows )
		{
			$this->skipTestIfRunningOnWindows();
		}

		$commandParts = \array_merge(['php', __DIR__ . '/test_commands/echo_args_as_json.php'], $args);

		$result = $this->object->executeRaw($commandParts);

		$this->assertEquals($args, $this->decodeEchoedArgs($result['output']));
	}

	private function skipTestIfRunningOnWindows()
	{
		if( $this->isRunningOnWindows() )
		{
			$this->markTestSkipped('Untestable on Windows');
		}
	}

	/**
	 * @return boolean
	 */
	private function isRunningOnWindows()
	{
		return \stripos(PHP_OS, 'WIN') === 0;
	}

	/**
	 * @param string $echoedArgs
	 * @return mixed
	 */
	private function decodeEchoedArgs( $echoedArgs )
	{
		return \json_decode($echoedArgs);
	}
}
