<?php
namespace XAF\test;

use PDO;
use Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Configuration;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Mapping\Driver\Driver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\Common\Annotations\AnnotationRegistry;

class DoctrineTestHelper
{
	/** @var PDO */
	static private $pdo;

	/** @var EntityManager */
	static private $em;

	static private $entityManagerClass = 'XAF\\test\\TestEntityManager';

	/** @var string 'annotation'|'xml' */
	static private $mappingType;

	/** @var string Path to entity classes or XML mapping files */
	static private $mappingPath;

	static private $proxyNamespace;

	/** @var array **/
	static private $entityNamespaces = [];

	static private $proxyPath;

	/** @var DebugStack */
	static private $debugStack;

	static public function setPdo( PDO $pdo )
	{
		self::$pdo = $pdo;
		// Make sure a really new EM will be created after a change of the DB connection
		self::$em = null;
	}

	static public function setEntityManagerClass( $class = 'Doctrine\\ORM\\EntityManager' )
	{
		self::$entityManagerClass = $class;
	}

	static public function setAnnotationMapping( $entityPath )
	{
		self::$mappingType = 'annotation';
		self::$mappingPath = $entityPath;
	}

	static public function setXmlMapping( $mappingFilePath )
	{
		self::$mappingType = 'xml';
		self::$mappingPath = $mappingFilePath;
	}

	static public function setEntityNamespaces( array $entityNamespaces )
	{
		self::$entityNamespaces = $entityNamespaces;
	}

	static public function setProxyNamespaceAndPath( $namespace, $path )
	{
		self::$proxyNamespace = $namespace;
		self::$proxyPath = $path;
	}

	static public function createDbTablesForAllEntities()
	{
		$em = self::getEm();

		$metadataFactory = $em->getMetadataFactory();
		$metadata = $metadataFactory->getAllMetadata();

		$schemaTool = new SchemaTool($em);
		$schemaTool->createSchema($metadata);
	}

	/**
	 * @return EntityManager
	 */
	static public function getNewEm()
	{
		self::resetEm();
		return self::getEm();
	}


	static public function resetEm()
	{
		if( self::$em && self::$em->isOpen() )
		{
			self::$em->clear();
			self::resetDebugStack();
		}
		else
		{
			// an EM will be closed when an exception ocurrs during flush,
			// it cannot be reopened, so a new EM must be created
			self::$em = null;
		}
	}

	static private function resetDebugStack()
	{
		if( self::$debugStack )
		{
			self::$debugStack->queries = [];
		}
	}

	/**
	 * @return EntityManager
	 */
	static public function getEm()
	{
		if( !self::$em )
		{
			self::$em = self::createEm();
		}
		return self::$em;
	}

	/**
	 * @return EntityManager
	 */
	static private function createEm()
	{
		$config = new Configuration();
		$config->setMetadataCacheImpl(new ArrayCache());
		$config->setQueryCacheImpl(new ArrayCache());

		$config->setMetadataDriverImpl(self::createMetadataDriver());

		$config->setProxyNamespace(self::$proxyNamespace);
		$config->setEntityNamespaces(self::$entityNamespaces);
		$config->setProxyDir(self::$proxyPath);
		$config->setAutoGenerateProxyClasses(true);

		self::$debugStack = new DebugStack();
		$config->setSQLLogger(self::$debugStack);

		$eventManager = new EventManager();
		$connection = DriverManager::getConnection(['pdo' => self::$pdo], $config, $eventManager);
		$emClass = self::$entityManagerClass;
		return new $emClass($connection, $config, $eventManager);
	}

	/**
	 * @return Driver
	 */
	static private function createMetadataDriver()
	{
		switch( self::$mappingType )
		{
			case 'annotation':
				return self::createAnnotationMetadataDriver();
			case 'xml':
				return self::createXmlMetadataDriver();
		}

		throw new Exception(
			self::$mappingType
			? 'Unknown metadata type: ' . self::$mappingType
			: 'metadata type not set'
		);
	}

	/**
	 * @return AnnotationDriver
	 */
	static private function createAnnotationMetadataDriver()
	{
		AnnotationRegistry::registerFile(LIB_PATH . '/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');
		$reader = new \Doctrine\Common\Annotations\SimpleAnnotationReader();
		$reader->addNamespace('Doctrine\\ORM\\Mapping');

		$reader = new \Doctrine\Common\Annotations\CachedReader($reader, new ArrayCache());

		return new AnnotationDriver($reader, [self::$mappingPath]);
	}

	/**
	 * @return XmlDriver
	 */
	static private function createXmlMetadataDriver()
	{
		return new XmlDriver(self::$mappingPath);

	}

	/**
	 * @return array
	 */
	static public function getSqlDebugStack()
	{
		return self::$debugStack->queries;
	}

	/**
	 * @return int
	 */
	static public function getSqlDebugStackSize()
	{
		return \sizeof(self::$debugStack->queries);
	}

	static public function getIssuedSqlStatements()
	{
		$sqlDebugStack = self::getSqlDebugStack();
		$result = [];
		foreach( $sqlDebugStack as $stackEntry )
		{
			$result[] = $stackEntry['sql'];
		}
		return $result;
	}

	/**
	 * @param string $pattern
	 * @param integer $index
	 * @return boolean
	 */
	static public function pregMatchDebugSqlStamentAtIndex( $pattern, $index )
	{
		$sqlStatements = self::getIssuedSqlStatements();
		if( !isset($sqlStatements[$index]) )
		{
			return false;
		}
		return \preg_match($pattern, $sqlStatements[$index]);
	}
}

