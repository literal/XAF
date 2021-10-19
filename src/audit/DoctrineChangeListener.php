<?php
namespace XAF\audit;

use Doctrine\Common\EventSubscriber;
use XAF\event\EventDispatcher;

use Doctrine\ORM\Event\OnFlushEventArgs;

/**
 * Forwards Doctrine onFlush event to an XAF event dispatcher
 */
class DoctrineChangeListener implements EventSubscriber
{
	/** @var EventDispatcher */
	private $eventDispatcher;

	function __construct( EventDispatcher $eventDispatcher )
	{
		$this->eventDispatcher = $eventDispatcher;
	}

	public function getSubscribedEvents()
	{
		return ['onFlush'];
	}

	public function onFlush( OnFlushEventArgs $args )
	{
		$uow = $args->getEntityManager()->getUnitOfWork();

		if( $uow->getScheduledEntityInsertions() || $uow->getScheduledEntityUpdates()
			|| $uow->getScheduledEntityDeletions() || $uow->getScheduledCollectionUpdates()
			|| $uow->getScheduledCollectionDeletions() )
		{
			$this->eventDispatcher->triggerEvent('doctrine.flush');
		}
	}
}
