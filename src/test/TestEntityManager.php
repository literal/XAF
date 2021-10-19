<?php
namespace XAF\test;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\Common\EventManager;

/**
 * Prevent data from being written to the DB by Doctrine 2
 *
 * For testing services that call flush() on the entity manager with incomplete test data that would cause
 * DB constraint violations.
 */
class TestEntityManager extends EntityManager
{
	/** @var bool */
	private $isFlushEnabled = true;

	/** @var int */
	private $flushCallCount = 0;

	/** @var int */
	private $clearCallCount = 0;

	public function __construct( Connection $conn, Configuration $config, EventManager $eventManager )
	{
		parent::__construct($conn, $config, $eventManager);
	}

	public function enableFlush()
	{
		$this->isFlushEnabled = true;
	}

	public function disableFlush()
	{
		$this->isFlushEnabled = false;
	}

	/**
	 * @return int
	 */
	public function getFlushCallCount()
	{
		return $this->flushCallCount;
	}

	/**
	 * @return int
	 */
	public function getClearCallCount()
	{
		return $this->clearCallCount;
	}

	public function flush( $entity = null )
	{
		$this->flushCallCount++;
		if( $this->isFlushEnabled )
		{
			parent::flush($entity);
		}
	}

	public function clear( $entityName = null )
	{
		$this->clearCallCount++;
		parent::clear($entityName);
	}
}

