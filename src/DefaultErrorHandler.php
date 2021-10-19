<?php
namespace XAF;

use XAF\log\error\ErrorLogger;
use XAF\exception\DebuggableError;

/**
 * There is no protection against recursion here as PHP prevents this anyway:
 * Warnings during error handling and exceptions during exception handling do not call their respective handlers.
 */
class DefaultErrorHandler implements ErrorHandler
{
	/** @var ErrorLogger */
	protected $errorLogger;

	/**
	 * type, message and location of last php error - saved to prevent double
	 * handling by error handler and shutdown handler
	 * @var string
	 */
	protected $lastHandledPhpError;

	/** @var bool whether to output error information */
	protected $displayDebugInfo = false;

	/** @var bool whether to output error information */
	protected $logExceptions = true;

	/** @var int */
	protected $phpErrorLogBitmask = 0xFFFF;

	/** @var int */
	protected $phpErrorTerminateBitmask = 0x0000;

	public function __construct( ErrorLogger $errorLogger )
	{
		$this->errorLogger = $errorLogger;
	}

	public function register()
	{
		\set_error_handler([$this, 'handleNonFatalPhpError']);
		\set_exception_handler([$this, 'handleException']);
		\register_shutdown_function([$this, 'shutdownHandler']);
	}

	/**
	 * @param bool $enabled
	 */
	public function setDisplayDebugInfo( $enabled )
	{
		$this->displayDebugInfo = $enabled;
	}

	/**
	 * @param bool $enabled
	 */
	public function setLogExceptions( $enabled )
	{
		$this->logExceptions = $enabled;
	}

	/**
	 * Set which types of PHP errors shall be logged (as far as they can be
	 * handled by a custom error handler)
	 *
	 * @param int $bitmask eg. E_NOTICE & E_WARNING
	 */
	public function setPhpErrorLoggingBitmask( $bitmask )
	{
		$this->phpErrorLogBitmask = $bitmask;
	}

	/**
	 * Set which types of non-fatal PHP errors shall cause the application to terminate
	 *
	 * @param int $bitmask eg. E_NOTICE & E_WARNING
	 */
	public function setPhpErrorTerminateBitmask( $bitmask )
	{
		$this->phpErrorTerminateBitmask = $bitmask;
	}

	/**
	 * @param \Throwable $e No type hint to maintain PHP 5.6 compatibility
	 */
	public function handleException( $e )
	{
		if( $this->logExceptions )
		{
			$this->logException($e);
		}

		$this->displayError(
			\is_object($e)
			  ? 'Exception of class ' . \get_class($e) . "\n" .
				(\method_exists($e, 'getMessage') ? 'Message: ' . $e->getMessage() . "\n" : '') .
				(\method_exists($e, 'getFile') && \method_exists($e, 'getLine')
					? 'Location: ' . $e->getFile() . ' (' . $e->getLine() . ')' . "\n"
					: ''
				) .
				(\method_exists($e, 'getTraceAsString') ? 'Trace: ' . "\n" . $e->getTraceAsString() : '')
			  : (\is_scalar($e) ? $e : '[' . \gettype($e) . ']')
		);

		exit(1);
	}

	/**
	 * Callback to be registered va set_error_handler() - this will only catch non-fatal
	 * errors. Fatal errors are intercepted by the shutdown handler.
	 *
	 * @param int $errorTypeNumber
	 * @param string $message
	 * @param string $file
	 * @param int $line
	 * @param array|null $localContext
	 * @return bool
	 */
	public function handleNonFatalPhpError( $errorTypeNumber, $message, $file, $line, $localContext )
	{
		// saved to prevent double logging here and later by the shutdown handler
		$this->lastHandledPhpError = $errorTypeNumber . '|' . $message . '|' . $file . '|' . $line;

		$errorClass = $this->decodePhpErrorNumber($errorTypeNumber);

		if( $errorTypeNumber & $this->phpErrorLogBitmask )
		{
			$this->logError(
				$errorClass,
				$message,
				\array_merge(
					[
						'location' => $file . ' (line ' . $line . ')',
						'context' => $localContext,
						'trace' => \debug_backtrace(false)
					],
					$this->getCommonDebugInfo()
				)
			);
		}

		if( $errorTypeNumber & $this->phpErrorTerminateBitmask )
		{
			$this->displayError(
				$errorClass . ' (PHP error type #' . $errorTypeNumber . ')' . "\n" .
				'Message: ' . $message . "\n" .
				'Location: ' . $file . ' (' . $line . ')'
			);
			exit(1);
		}

		// Pass on error handling/display to PHP
		return false;
	}

	protected function decodePhpErrorNumber( $errorNumber )
	{
		$codes = [
			\E_ERROR => 'php fatal error',
			\E_RECOVERABLE_ERROR => 'php error',
			\E_WARNING => 'php warning',
			\E_PARSE => 'php parse error',
			\E_NOTICE => 'php notice',
			\E_STRICT => 'php strict warning',
			\E_DEPRECATED => 'php deprecated warning',
			\E_CORE_ERROR => 'php core error',
			\E_CORE_WARNING => 'php core warning',
			\E_COMPILE_ERROR => 'php compile error',
			\E_COMPILE_WARNING => 'php compile warning',
			\E_USER_ERROR => 'error',
			\E_USER_WARNING => 'warning',
			\E_USER_NOTICE => 'notice',
			\E_USER_DEPRECATED => 'deprecated'
		];

		return $codes[$errorNumber] ?? 'php error #' . $errorNumber;
	}

	/**
	 * As the registered error handler will only catch non-fatal errors, this shutdown handler
	 * will try to also get the fatal ones - a shutdown function will even be called after a
	 * fatal error has occurred.
	 *
	 * Error context and backtrace are not available, though.
	 */
	public function shutdownHandler()
	{
		$error = \error_get_last();

		if( !$error || !($error['type'] & $this->phpErrorLogBitmask) )
		{
			return;
		}

		// make sure this is not the same as the last error logged by handlePhpError()
		if( $this->lastHandledPhpError == $error['type'] . '|' . $error['message'] . '|' . $error['file'] . '|' . $error['line'] )
		{
			return;
		}

		$errorClass = $this->decodePhpErrorNumber($error['type']);
		$this->logError(
			$errorClass,
			$error['message'],
			\array_merge(
				['location' => $error['file'] . ' (line ' . $error['line'] . ')'],
				$this->getCommonDebugInfo()
			)
		);
	}

	/**
	 * @param \Throwable $e No type hint to maintain PHP 5.6 compatibility
	 * @param bool $withTrace
	 */
	public function logException( $e, $withTrace = true )
	{
		$this->logError(
			$this->getExceptionLogClassKey($e),
			(\is_object($e) ? \get_class($e) . ': ' : '')
				. (\method_exists($e, 'getMessage')
					? $e->getMessage()
					: (\is_scalar($e) ? $e : '[' . \gettype($e) . ']')
				),
			\array_merge($this->getExceptionDebugInfo($e, $withTrace), $this->getCommonDebugInfo())
		);
	}

	/**
	 * @param \Throwable $e No type hint to maintain PHP 5.6 compatibility
	 * @return string
	 */
	protected function getExceptionLogClassKey( $e )
	{
		return \is_object($e) && \method_exists($e, 'getLogClass') ? $e->getLogClass() : 'exception';
	}

	/**
	 * @param \Throwable $e No type hint to maintain PHP 5.6 compatibility
	 * @param bool $withTrace
	 * @return array|null
	 */
	protected function getExceptionDebugInfo( $e, $withTrace = true )
	{
		$result = [];

		if( \is_object($e) )
		{
			if( $e instanceof DebuggableError )
			{
				foreach( (array)$e->getDebugInfo() as $k => $v )
				{
					if( isset($v) )
					{
						$result[$k] = $v;
					}
				}
			}

			$result['exception'] = ['class' => \get_class($e)];

			if( \method_exists($e, 'getFile') && \method_exists($e, 'getLine') )
			{
				$result['exception']['location'] = $e->getFile() . ' (line ' . $e->getLine() . ')';
			}

			if( $withTrace && \method_exists($e, 'getTrace') )
			{
				$result['exception']['trace'] = $e->getTrace();
			}
		}

		return $result;
	}

	/**
	 * @param string $errorClass
	 * @param string $message
	 * @param array $debugInfo
	 */
	public function logError( $errorClass, $message, $debugInfo = [] )
	{
		$this->errorLogger->logError($errorClass, $message, $debugInfo);
	}

	/**
	 * To be extended by derived classes to provide environment specific
	 * debug info, e.g. the request details in a web app
	 *
	 * @return array
	 */
	protected function getCommonDebugInfo()
	{
		return [
			'memory' => [
				'current' => \memory_get_usage(true),
				'peak' => \memory_get_peak_usage(true)
			]
		];
	}

	protected function displayError( $debugMessage )
	{
		echo 'fatal error' . "\n";
		if( $this->displayDebugInfo )
		{
			echo "\n" . $debugMessage;
		}
	}
}
