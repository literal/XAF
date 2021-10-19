<?php
namespace XAF\helper;

/**
 * Execution shell commands with argument escaping and error handling
 *
 * IMPORTANT WINDOWS NOTE:
 *     Argument escaping on Windows is rather unpredictable - the Windows command shell (cmd.exe)
 *     passes double quoted arguments to the invoked application in raw format, i. e. with the surrounding
 *     quotes and all contained escape sequences. So it depends on the actual application whether an escape
 *     sequence like '\"' will be interpreted or not.
 */
class ShellCommandRunner
{
	/** @var bool */
	protected $isRunningOnWindows;

	public function __construct()
	{
		$this->isRunningOnWindows = \stripos(PHP_OS, 'WIN') === 0;
	}

	/**
	 * Execute the command throwing an exception when the return code is != 0
	 *
	 * @param array $commandParts The items on the command line to be escaped and separated by spaces
	 * @return string The command's output
	 */
	public function execute( array $commandParts )
	{
		$commandLine = $this->buildCommandLine($commandParts);
		$result = $this->executeCommandLine($commandLine);
		if( $result['returnCode'] !== 0 )
		{
			throw new ShellCommandException('failed executing process', $result);
		}
		return $result['output'];
	}

	/**
	 * Execute the command without evaluation of the outcome, returning also the return code for evaluation
	 * by the client code
	 *
	 * @param array $commandParts The items on the command line to be escaped and separated by spaces
	 * @return array {commandLine: <string>, output: <string>, returnCode: <int>}
	 */
	public function executeRaw( array $commandParts )
	{
		$commandLine = $this->buildCommandLine($commandParts);
		return $this->executeCommandLine($commandLine);
	}

	/**
	 * @param array $commandParts
	 * @return string
	 */
	protected function buildCommandLine( array $commandParts )
	{
		$commandParts = \array_map([$this, 'escapeArgument'], $commandParts);
		return \implode(' ', $commandParts) . ' 2>&1';
	}

	/**
	 * Replacement for buggy PHP escapeshellarg() (it removes UTF8 multibyte sequences).
	 *
	 * @param string $argument
	 * @return string
	 */
	protected function escapeArgument( $argument )
	{
		return $this->isRunningOnWindows
			? '"' . \str_replace('"', '\\"', $argument) . '"'
			: "'" . \str_replace("'", "'\\''", $argument) . "'";
	}

	/**
	 * @param string $commandLine
	 * @return array {commandLine: <string>, returnCode: <int>, output: <string>}
	 */
	protected function executeCommandLine( $commandLine )
	{
		\exec($commandLine, $outputLines, $returnCode);
		return [
			'commandLine' => $commandLine,
			'returnCode' => $returnCode,
			'output' => \implode("\n", $outputLines)
		];
	}
}
