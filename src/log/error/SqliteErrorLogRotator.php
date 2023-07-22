<?php
namespace XAF\log\error;

use XAF\helper\ShellCommandRunner;
use XAF\file\FileNameHelper;

use DateTime;
use Exception;

class SqliteErrorLogRotator
{
	/** @var ShellCommandRunner */
	private $shellCommandRunner;

	/** @var string */
	private $pathToSqlite3Executable;

	/** @var string Name der DB-Tabelle, in die geschrieben wird */
	private $tableName;

	public function __construct( ShellCommandRunner $shellCommandRunner, $pathToSqlite3Executable = 'sqlite3',
		$tableName = 'errorlog' )
	{
		$this->shellCommandRunner = $shellCommandRunner;
		$this->pathToSqlite3Executable = $pathToSqlite3Executable;
		$this->tableName = $tableName;
	}

	public function rotate( $sqliteFile, $maxEntryAgeDays )
	{
		$backupFile = $this->backupLiveDb($sqliteFile);
		$cutoffTimestamp = $this->computeCutoffTimestamp($maxEntryAgeDays);
		$this->runSqliteCommand($backupFile, 'DELETE FROM ' . $this->tableName . ' WHERE timestamp >= ' . $cutoffTimestamp);
		$this->runSqliteCommand($sqliteFile, 'DELETE FROM ' . $this->tableName . ' WHERE timestamp < ' . $cutoffTimestamp);
		$this->runSqliteCommand($backupFile, 'VACUUM');
		$this->runSqliteCommand($sqliteFile, 'VACUUM');
	}

	/**
	 * @param string $liveDbPath
	 * @return string Full path to the created backup file
	 */
	private function backupLiveDb( $liveDbPath )
	{
		$dbFolder = FileNameHelper::extractDirectoryPath($liveDbPath);
		$dbFolder = $dbFolder === '' ? '.' : $dbFolder;
		$dbBaseName = FileNameHelper::extractBasename($liveDbPath);
		$dbExtension = FileNameHelper::extractExtension($liveDbPath);

		$this->rotateExistingBackupFilesIfPresent($dbFolder, $dbBaseName, 1);

		$backupDbPath = $dbFolder . '/' . $dbBaseName . '.1.' . $dbExtension;
		$this->runSqliteCommand($liveDbPath, '.backup "' . $backupDbPath . '"');

		return $backupDbPath;
	}

	/**
	 * @param string $folder
	 * @param string $baseName
	 * @param int $generationNumber
	 */
	private function rotateExistingBackupFilesIfPresent( $folder, $baseName, $generationNumber )
	{
		$filesToRotate = \glob($folder . '/' . $baseName . '.' . $generationNumber . '.*');
		if( $filesToRotate )
		{
			// First, move any existing files belonging to the target generation out of the way
			$this->rotateExistingBackupFilesIfPresent($folder, $baseName, $generationNumber + 1);
			foreach( $filesToRotate as $fileToRotate )
			{
				$this->moveBackupFileDownOneGeneration($fileToRotate);
			}
		}
	}

	/**
	 * @param string $filePath
	 */
	private function moveBackupFileDownOneGeneration( $filePath )
	{
		$folder = FileNameHelper::extractDirectoryPath($filePath);
		$folder = $folder === '' ? '.' : $folder;
		$fileName = FileNameHelper::extractName($filePath);

		if( !\preg_match('/(.+)\\.([1-9]\\d*)\\.(.+)/', $fileName, $matches) )
		{
			throw new Exception('Existing backup file name does not match expected pattern: ' . $fileName);
		}

		$baseName = $matches[1];
		$backupNumber = \intval($matches[2]);
		$extension = $matches[3];
		$targetPath = $folder . '/' . $baseName . '.' . ($backupNumber + 1) . '.' . $extension;

		if( !\rename($filePath, $targetPath) )
		{
			throw new Exception('Failed rename file from ' . $filePath . ' to ' . $targetPath);
		}
	}

	/**
	 * @param string $dbFile
	 * @param string $command
	 */
	private function runSqliteCommand( $dbFile, $command )
	{
		$this->shellCommandRunner->execute([$this->pathToSqlite3Executable, $dbFile, $command]);
	}

	/**
	 * @param int $daysAgo
	 * @return int
	 */
	private function computeCutoffTimestamp( $daysAgo )
	{
		$d = new DateTime('-' . $daysAgo . ' days');
		return $d->getTimestamp();
	}
}
