<?php
namespace XAF\db;

use PDO, PDOStatement;

/**
 * PDO wrapper adding lazy initialization and shorthand methods for typical query use cases
 *
 * The class is intended for DB *use*, so connection and configuration details are not
 * exposed
 *
 * The direct query methods exec(), queryValue(), queryRow() and queryTable() all take
 * very flexible arguments for the query parameters. All scalar values passed after the SQL-
 * string will be included in the params array, all arrays passed will be merged with the params
 * array. The following examples are all equivalent:
 * - exec('UPDATE foo SET bar = ?, baz = ? WHERE id = ?', 'bar', 12, 23);
 * - exec('UPDATE foo SET bar = ?, baz = ? WHERE id = ?', ['bar', 12, 23]);
 * - exec('UPDATE foo SET bar = ?, baz = ? WHERE id = ?', 'bar', [12, 23]);
 * - exec('UPDATE foo SET bar = ?, baz = ? WHERE id = ?', ['bar'], [12, 23]);
 * - exec('UPDATE foo SET bar = :bar, baz = :baz WHERE id = :id', ['bar' => 'bar', 'baz' => 12, 'id' => 23]);
 */
class Dbh
{
	/** @var string */
	private $dsn;

	/** @var string|null */
	private $user;

	/** @var string|null */
	private $password;

	/** @var array */
	private $pdoOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

	/** @var PDO */
	private $pdo;

	/**
	 * @param PDO|string $dsnOrPdo
	 * @param string|null $user
	 * @param string|null $password
	 * @param array $pdoOptions
	 */
	public function __construct( $dsnOrPdo, $user = null, $password = null, array $pdoOptions = [] )
	{
		if( $dsnOrPdo instanceof PDO )
		{
			$this->pdo = $dsnOrPdo;
		}
		else
		{
			$this->dsn = $dsnOrPdo;
			$this->user = $user;
			$this->password = $password;
			$this->pdoOptions = \array_replace($this->pdoOptions, $pdoOptions);
		}
	}

	/**
	 * @return PDO
	 */
	public function getPdo()
	{
		if( !$this->pdo )
		{
			$this->pdo = new PDO($this->dsn, $this->user, $this->password, $this->pdoOptions);
		}
		return $this->pdo;
	}

	/**
	 * @see PDO::prepare
	 * @param string $sql
	 * @return PDOStatement
	 */
	public function prepare( $sql )
	{
		return $this->getPdo()->prepare($sql);
	}

	/**
	 * Prepares a statement and executes it, returning the affectd row count
	 *
	 * @param string $sql
	 * @param mixed ... query params can be passed as an array or multiple arguments or a combination thereof
	 * @return int affected row count
	 */
	public function exec( $sql )
	{
		$stmt = $this->prepareAndExec(\func_get_args());
		$affectedRowCount = $stmt->rowCount();
		$stmt->closeCursor();
		return $affectedRowCount;
	}

	/**
	 * Prepares a statement, executes it, closes it and returns the value from the first field of
	 * the first result row.
	 *
	 * Useful for cases like e.g. 'SELECT COUNT(*) FROM ...'
	 *
	 * @param string $sql
	 * @param mixed ... query params can be passed as an array or multiple arguments or a combination thereof
	 * @return mixed FALSE if query produced no result
	 */
	public function queryValue( $sql )
	{
		$stmt = $this->prepareAndExec(\func_get_args());
		$value = $stmt->fetchColumn(0);
		$stmt->closeCursor();
		return $value;
	}

	/**
	 * Prepares a statement, executes it, closes it and returns the first result row as a hashmap
	 * (result mode PDO::FETCH_ASSOC)
	 *
	 * Useful for cases like e.g. 'SELECT ... FROM ... WHERE id = ?'
	 *
	 * @param string $sql
	 * @param mixed ... query params can be passed as an array or multiple arguments or a combination thereof
	 * @return mixed FALSE if query produced no result
	 */
	public function queryRow( $sql )
	{
		$stmt = $this->prepareAndExec(\func_get_args());
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt->closeCursor();
		return $row;
	}

	/**
	 * Prepares a statement, executes it, closes it and returns an scalar array of the values
	 * from first result column
	 *
	 * Useful for cases like e.g. 'SELECT id FROM ...'
	 *
	 * @param string $sql
	 * @param mixed ... query params can be passed as an array or multiple arguments or a combination thereof
	 * @return mixed FALSE if query produced no result
	 */
	public function queryColumn( $sql )
	{
		$stmt = $this->prepareAndExec(\func_get_args());
		return $stmt->fetchAll(PDO::FETCH_COLUMN);
	}

	/**
	 * Prepares a statement, executes it, and returns complete result as an array of hashmaps
	 * (result mode PDO::FETCH_ASSOC)
	 *
	 * @param string $sql
	 * @param mixed ... query params can be passed as an array or multiple arguments or a combination thereof
	 * @return array empty array if query produced no result
	 */
	public function queryTable( $sql )
	{
		$stmt = $this->prepareAndExec(\func_get_args());
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * @param array $args
	 * @return PDOStatement
	 */
	private function prepareAndExec( array $args )
	{
		$sql = \array_shift($args);
		$queryParams = [];
		foreach( $args as $arg )
		{
			if( \is_array($arg) )
			{
				$queryParams = \array_merge($queryParams, $arg);
			}
			else
			{
				$queryParams[] = $arg;
			}
		}

		$stmt = $this->getPdo()->prepare($sql);
		$stmt->execute($queryParams);
		return $stmt;
	}

	/**
	 * @see PDO::beginTransaction
	 */
	public function beginTransaction()
	{
		$this->getPdo()->beginTransaction();
	}

	/**
	 * @see PDO::rollBack
	 */
	public function rollBack()
	{
		$this->getPdo()->rollBack();
	}

	/**
	 * @see PDO::commit
	 */
	public function commit()
	{
		$this->getPdo()->commit();
	}

	/**
	 * @param string $sequenceName required for Postgres
	 * @return int
	 */
	public function getLastInsertId( $sequenceName = null )
	{
		return \intval($this->getPdo()->lastInsertId($sequenceName));
	}

	/**
	 * @see PDO::quote
	 * @param string $string
	 * @param int $paramType any of the PDO::PARAM_* constants
	 */
	public function quote( $string, $paramType = PDO::PARAM_STR )
	{
		return $this->getPdo()->quote($string, $paramType);
	}

	/**
	 * Get contents of a queried blob field as a string
	 *
	 * This is a work-around for a PDO peculiarity:
	 * While MySQL BLOBs contents are returned as strings, PostgreSQL BYTEA contents
	 * are returned as stream handles, from which the contents have to be read
	 * But if a BYTEA column contains a NULL value, it will be returned directly (of
	 * course, null cannot be read from a stream)
	 *
	 * @param mixed $fieldValue a blob field value returned from a query
	 * @return string|null the blob contents as a binary string
	 */
	static public function unwrapBlobContents( $fieldValue )
	{
		return \is_resource($fieldValue) && \get_resource_type($fieldValue) == 'stream'
			? \stream_get_contents($fieldValue)
			: $fieldValue;
	}
}
