<?php
namespace XAF\db;

use PDO;

/**
 * Helper for unit tests, integration tests, deployment scripts etc.
 * Not normally used in applications
 */
class DbHelper
{
	static private $implementationClassName;
	static private $implementation;

	static public function setImplementationClass( $className )
	{
		self::$implementation = null;
		self::$implementationClassName = $className;
	}

	static private function getImplementation()
	{
		if( !self::$implementation )
		{
			self::$implementation = new self::$implementationClassName;
		}
		return self::$implementation;
	}

	static public function setDb( $host, $db, $user = null, $password = null )
	{
		$imp = self::getImplementation();
		$imp->setDb($host, $db, $user, $password);
	}

	static public function setPdoOption( $key, $value )
	{
		$imp = self::getImplementation();
		return $imp->setPdoOption($key, $value);
	}

	/**
	 * @return PDO
	 */
	static public function getPdo()
	{
		$imp = self::getImplementation();
		return $imp->getPdo();
	}

	static public function clearDb()
	{
		$imp = self::getImplementation();
		$imp->clearDb();
	}

	static public function clearAllTables()
	{
		$imp = self::getImplementation();
		$imp->clearAllTables();
	}

	static public function clearTable( $tableName )
	{
		$imp = self::getImplementation();
		$imp->clearTable($tableName);
	}

	static public function synchronizeAllSequences()
	{
		$imp = self::getImplementation();
		return $imp->synchronizeAllSequences();
	}

	static public function runSqlScript( $filename )
	{
		$imp = self::getImplementation();
		$imp->runSqlScript($filename);
	}

	static public function execCommand( $sql )
	{
		$pdo = self::getPdo();
		$pdo->exec($sql);
	}
}

