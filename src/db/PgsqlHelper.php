<?php
namespace XAF\db;

use PDO;

/**
 * Helper for unit tests, integration tests, deployment scripts etc.
 * Not normally used in applications
 */
class PgsqlHelper
{
	private $dsn;
	private $user;
	private $password;
	private $pdoOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

	/** @var PDO */
	private $pdo;

	public function setDb( $host, $db, $user = null, $password = null )
	{
		$this->dsn = 'pgsql:host=' . $host . ';dbname=' . $db . ';';
		$this->user = $user;
		$this->password = $password;

		$this->pdo = null;
	}

	public function setPdoOption( $key, $value )
	{
		$this->pdoOptions[$key] = $value;
		$this->pdo = null;
	}

	public function setPdo( PDO $pdo )
	{
		$this->pdo = $pdo;
	}

	/**
	 * @return PDO
	 */
	public function getPdo()
	{
		if( !$this->pdo )
		{
			$this->pdo = new PDO(
				$this->dsn,
				$this->user,
				$this->password,
				$this->pdoOptions
			);
		}
		return $this->pdo;
	}

	public function clearDb()
	{
		$pdo = $this->getPdo();
		$pdo->exec('DROP SCHEMA public CASCADE');
		$pdo->exec('CREATE SCHEMA public');
	}

	public function dropAllTables()
	{
		$pdo = $this->getPdo();
		foreach( $this->getAllTableNames() as $tableName )
		{
			$pdo->exec('DROP TABLE ' . $tableName . ' CASCADE');
		}
	}

	public function clearAllTables()
	{
		$pdo = $this->getPdo();
		$tableNames = $this->getAllTableNames();
		if( $tableNames )
		{
			$pdo->exec('TRUNCATE TABLE ' . \implode(', ', $tableNames));
		}
	}

	public function clearTable( $tableName )
	{
		$pdo = $this->getPdo();
		$pdo->exec('DELETE FROM ' . $tableName);

	}

	protected function getAllTableNames()
	{
		$pdo = $this->getPdo();
		$stmt = $pdo->query(
			'SELECT table_name' .
			' FROM information_schema.tables' .
			' WHERE table_schema = \'public\' AND table_type = \'BASE TABLE\''
		);
		return $stmt->fetchAll(PDO::FETCH_COLUMN);
	}

	public function dropAllSequences()
	{
		$pdo = $this->getPdo();
		foreach( $this->getAllSequenceNames() as $sequenceName )
		{
			$pdo->exec('DROP SEQUENCE ' . $sequenceName);
		}
	}

	protected function getAllSequenceNames()
	{
		$pdo = $this->getPdo();
		$stmt = $pdo->query('SELECT sequence_name FROM information_schema.sequences WHERE sequence_schema = \'public\'');
		return $stmt->fetchAll(PDO::FETCH_COLUMN);
	}

	public function dropAllViews()
	{
		$pdo = $this->getPdo();
		foreach( $this->getAllViewNames() as $viewName )
		{
			$pdo->exec('DROP VIEW ' . $viewName);
		}
	}

	protected function getAllViewNames()
	{
		$pdo = $this->getPdo();
		$stmt = $pdo->query('SELECT table_name FROM information_schema.views WHERE table_schema = \'public\'');
		return $stmt->fetchAll(PDO::FETCH_COLUMN);
	}

	/**
	 * Set all sequence generators to corresponding column's maximum ID-values + 1
	 * Use after inserting records with explicit IDs, e.g. from a fixture
	 *
	 * Works only for ID columns called 'id' and corresponding sequences called '<table-name>_id_seq'
	 * @return int number of synchronized sequences
	 */
	public function synchronizeAllSequences()
	{
		$count = 0;
		foreach( $this->getAllTableNames() as $tableName )
		{
			$sequenceNameCandidate = $tableName . '_id_seq';
			if( $this->sequenceExists($sequenceNameCandidate) )
			{
				$count++;
				$this->synchronizeSequence($tableName, 'id', $sequenceNameCandidate);
			}
		}
		return $count;
	}

	/**
	 * @param string $sequenceName
	 * @return bool
	 */
	protected function sequenceExists( $sequenceName )
	{
		$pdo = $this->getPdo();
		$stmt = $pdo->prepare('SELECT 1 FROM information_schema.sequences WHERE sequence_schema = ? AND sequence_name = ?');
		$stmt->execute(['public', $sequenceName]);
		$result = $stmt->fetch(PDO::FETCH_COLUMN);
		$stmt->closeCursor();
		return $result !== false;
	}

	/**
	 * Set a sequence generator to a table's maximum ID-values + 1
	 * Use after inserting records with explicit IDs, e.g. from a fixture
	 */
	public function synchronizeSequence( $tableName, $idFieldName = 'id', $sequenceName = null )
	{
		if( $sequenceName === null )
		{
			$sequenceName = $tableName . '_' . $idFieldName . '_seq';
		}

		$pdo = $this->getPdo();
		$stmt = $pdo->query('SELECT MAX(' . $idFieldName . ') FROM ' . $tableName);
		$maxId = $stmt->fetch(PDO::FETCH_COLUMN);
		$stmt->closeCursor();
		if( $maxId === false )
		{
			$maxId = 0;
		}

		$pdo->exec('ALTER SEQUENCE ' . $sequenceName . ' RESTART WITH ' . ($maxId + 1));
	}

	public function runSqlScript( $filename )
	{
		$sql = \file_get_contents($filename);
		$pdo = $this->getPdo();
		$pdo->exec($sql);
	}
}

