<?php
namespace XAF\log\error;

use PHPUnit\Framework\TestCase;
use XAF\test\TestFileManagement;

use Exception;
use PDO;

/**
 * This is merely a cheap smoke test as the contents of the created log entries is nearly impossible
 * to predict and the test cannot run in a web server environment
 *
 * @covers \XAF\log\error\SqliteErrorLogger
 */
class SqliteErrorLoggerTest extends TestCase
{
	use TestFileManagement;

	static protected $sqliteFile;

	/** @var PDO */
	static protected $pdo;

	static protected $phpErrorLogFile;

	static public function setUpBeforeClass(): void
	{
		// Do not create/remove test files before each test, because they are kept open after the tests, so
		// trying to delete them will fail.
		$workPath = WORK_PATH . '/sqlite-errlog';
		self::createAndRegisterTestFolder($workPath);
		self::$sqliteFile = $workPath . '/errlog.sqlite';
		self::$phpErrorLogFile = $workPath . '/phperrors.log';

		\ini_set('log_errors', 1);
		\ini_set('error_log', self::$phpErrorLogFile);

		self::$pdo = new PDO(
			'sqlite:' . self::$sqliteFile,
			null,
			null,
			[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
		);
	}

	protected function tearDown(): void
	{
		// Prevent removal of test files, because they are still open, so trying to delete them will fail
	}

	public function testLogErrorToDb()
	{
		$logger = new SqliteErrorLogger('testapp', self::$sqliteFile);
		$rowCount = $this->getTableRowCount('errorlog');

		$logger->logError('someclass', 'message');

		$this->assertEquals($rowCount + 1, $this->getTableRowCount('errorlog'));
	}

	public function testPhpErrorLogFallbackIfInvalidDb()
	{
		$logger = new SqliteErrorLogger('testapp', './non_existing_dir/non_existing_file');

		$logger->logError('someclass', 'message');

		$this->assertTrue(\file_exists(self::$phpErrorLogFile), 'error log file not created');
		$this->assertTrue(\filesize(self::$phpErrorLogFile) > 0, 'error log file empty');
	}

	protected function getTableRowCount( $tableName )
	{
		try
		{
			$stmt = self::$pdo->prepare('SELECT COUNT(*) FROM ' . $tableName);
			$stmt->execute();
			$rowCount = $stmt->fetchColumn();
			$stmt->closeCursor();
			return $rowCount;
		}
		// table does not yet exist
		catch( Exception $e )
		{
			// cannot query sqlite_master to check if table exists, because
			// two connections are involved here and a schema error occurrs
			// @see http://bugs.php.net/bug.php?id=43942
			return 0;
		}
	}

}
