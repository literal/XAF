<?php
namespace XAF\audit;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use XAF\log\audit\AuditLogger;
use Doctrine\ORM\Proxy\Proxy;

use DateTime;
use XAF\log\audit\StructuredAuditLogEntry;

/**
 * Collects, assembles and augments all pending changes from the Doctrine 2 entity manager
 *
 * Format of generated log entry data (log format key "ocs-v1", "ocs" stands for "object changeset"):
 * [
 *     // For a new child entity or a new reference to an existing foreign entity:
 *     [<name>, '+', <object-descriptor>},
 *
 *     // For a deleted child entity or a removed reference to an existing foreign entity:
 *     [<name>, '-', <object-desciptor>],
 *
 *     // For a property change:
 *     [<name>, '~', <old value>, <new value>],
 *
 *     ...
 * ]
 * - <name> is either a field name or a colon separated chain like <collection name>:<item marker>:<item field name>...
 *   where the item marker may be an id, rank etc.
 * - <object-desciptor> is a string consisting of type, id and optional description of the referenced entity
 *
 * To enable advanced change logging, implement method __getAuditInfo() on entities.
 * Method shall return a hash, all fields are optional:
 * {
 *     type: <string>,     // Entity type, if not specified, the class name (relative to the entity root NS) is used
 *     id: <string>,       // Will be fetched from UOW if not specified
 *                         //   ATTENTION: For DBMS where the ID is generated on insert (as opposed to using a sequence
 *                         //   in advance) this field MUST be returned to avoid "undefined index" warnings in the UOW!
 *     label: <string>,    // Human-readable summary of the object identity
 *     owner: <object>     // Root entity under which to file changes/creation/deletion of this entity
 *     mapAs: <string>     // Field name prefix to use when appending to owner's field changes, does *not* have to
 *                         // be the name of a mapped collection field
 * }
 *
 * @todo Recursive ownership may lead to inconsistent/duplicate log entries: Transferring dependent entity changes
 *   to their owner's change sets is implemented as a single pass operation. So if a child is moved to its parent and
 *   thus removed from the global change set list, and a grandchild is processed afterwards, the grandchild will
 *   not find its parent and a new change set for that parent will created.
 *
 * @todo Would be nice to record values of deleted entities -> $this->uow->getOriginalEntityData($entity);
 *
 * @todo Allow different audit info labels depending on use as root entity or referenced entity
 *     example Price: When edited, provider is interesting, when referenced by a product, not so much
 */
class DoctrineChangeLogger
{
	const OPERATION_CLASS_KEY = 'doctrine';
	const DATA_FORMAT_KEY = 'ocs-v1';

	/** @var string Will be stripped from entity class names before logging */
	private $entityRootNamespace;

	/** @var EntityManager */
	private $em;

	/** @var AuditLogger */
	private $logger;

	/** @var array Indexed by entity OIDs */
	private $changeSets;

	function __construct( EntityManager $em, AuditLogger $logger, $entityRootNamespace = '' )
	{
		$this->em = $em;
		$this->logger = $logger;
		$this->setEntityRootNamespace($entityRootNamespace);
	}

	/**
	 * @param string $namespace Common namespace to be stripped from the beginning of entity class names
	 *     when logging object types
	 */
	public function setEntityRootNamespace( $namespace )
	{
		$namespace = \trim($namespace, '\\');
		$this->entityRootNamespace = ($namespace !== '' ? $namespace . '\\' : '');
	}

	/**
	 * To be called in the "onFlush" phase of the entity manager
	 */
	public function logScheduledChanges()
	{
		$this->changeSets = [];

		$this->collectEntityChanges();
		$this->collectCollectionChanges();
		$this->compactChangeSets();
		$this->logChangeSets();

		// To enable garbage collection
		$this->changeSets = [];
	}

	private function collectEntityChanges()
	{
		$uow = $this->em->getUnitOfWork();

		foreach( $uow->getScheduledEntityUpdates() as $entity )
		{
			$this->addEntityChangeSet($entity, 'upd');
		}
		foreach( $uow->getScheduledEntityInsertions() as $entity )
		{
			$this->addEntityChangeSet($entity, 'ins');
		}
		foreach( $uow->getScheduledEntityDeletions() as $entity )
		{
			$this->addEntityChangeSet($entity, 'del');
		}
	}

	/**
	 * @param object $entity
	 * @param string $operation 'ins', 'upd' or 'del'
	 */
	private function addEntityChangeSet( $entity, $operation )
	{
		$isNewEntity = ($operation == 'ins');
		$entry = \array_merge(
			[
				'op' => $operation,
				'obj' => $entity,
				'changes' => []
			],
			$this->getEntityAuditInfo($entity)
		);

		foreach( $this->em->getUnitOfWork()->getEntityChangeSet($entity) as $fieldName => $values )
		{
			list($oldValue, $newValue) = $values;

			// Do not log null fields on new entities
			if( $isNewEntity && $newValue === null )
			{
				continue;
			}

			// Do not record a dependent entity's reference to it's owner. The information
			// is already represented by moving it into the owner's change set.
			if( isset($entry['owner']) && $newValue === $entry['owner'] )
			{
				continue;
			}

			$oldValueExport = $this->exportFieldValue($oldValue);
			$newValueExport = $this->exportFieldValue($newValue);

			// Sometimes Doctrine has false positives for changes, e.g. for DateTime objects replaced by
			// new DateTime objects with equivalent value. Comparing the exported values reveals these cases.
			if( $oldValueExport === $newValueExport )
			{
				continue;
			}

			// '~' is the marker for "change" as opposed to '+' and '-' for inserts and deletes
			$change = [$fieldName, '~'];

			// Do not record null values to keep the data compact
			if( $oldValueExport !== null )
			{
				$change[2] = $oldValueExport;
			}
			if( $newValueExport !== null )
			{
				$change[3] = $newValueExport;
			}

			// For new entities there is no such thing as an 'old' value
			$entry['changes'][] = $change;
		}

		$oid = \spl_object_hash($entity);
		if( isset($this->changeSets[$oid]) )
		{
			$entry['changes'] = \array_merge($this->changeSets[$oid]['changes'], $entry['changes']);
		}
		$this->changeSets[$oid] = $entry;
	}

	/**
	 * Put all dependent objects' change entries below their owner's
	 */
	private function compactChangeSets()
	{
		foreach( $this->changeSets as $oid => $changeSet )
		{
			if( isset($changeSet['owner']) )
			{
				$this->moveDependentEntityChangesToOwner($oid);
			}
		}
	}

	/**
	 * @param string $oid
	 */
	private function moveDependentEntityChangesToOwner( $oid )
	{
		$changeSet = $this->changeSets[$oid];
		unset($this->changeSets[$oid]);

		$owner = $changeSet['owner'];
		$ownerFieldName = $changeSet['mapAs'] ?? '';

		if( $changeSet['op'] == 'del' )
		{
			$this->recordDependentEntityRemoval($owner, $ownerFieldName, $changeSet['obj']);
		}
		else if( $changeSet['op'] == 'ins' )
		{
			$this->recordDependentEntityAddition($owner, $ownerFieldName, $changeSet['obj']);
		}

		$this->recordDependentObjectChanges($owner, $ownerFieldName, $changeSet['changes']);
	}

	private function collectCollectionChanges()
	{
		$uow = $this->em->getUnitOfWork();

		foreach( $uow->getScheduledCollectionUpdates() as $collection )
		{
			$this->addCollectionDifferencesToOwnerChangeSet(
				$collection->getOwner(),
				$collection->getMapping()['fieldName'],
				$collection->getSnapshot(),
				$collection->toArray()
			);
		}

		foreach( $uow->getScheduledCollectionDeletions() as $collection )
		{
			$this->addCollectionDifferencesToOwnerChangeSet(
				$collection->getOwner(),
				$collection->getMapping()['fieldName'],
				$collection->getSnapshot(),
				[]
			);
		}
	}

	/**
	 * @param object $owner
	 * @param string $fieldName
	 * @param array $originalItems
	 * @param array $currentItems
	 */
	private function addCollectionDifferencesToOwnerChangeSet( $owner, $fieldName, array $originalItems, array $currentItems )
	{
		$removedItems = $this->getItemsMissingFromOtherSet($originalItems, $currentItems);
		foreach( $removedItems as $removedItem )
		{
			$this->recordDependentEntityRemoval($owner, $fieldName, $removedItem);
		}

		$addedItems = $this->getItemsMissingFromOtherSet($currentItems, $originalItems);
		foreach( $addedItems as $addedItem )
		{
			$this->recordDependentEntityAddition($owner, $fieldName, $addedItem);
		}
	}

	/**
	 * Returns all items found in $set which are not present in $referenceSet
	 *
	 * @param array $set
	 * @param array $referenceSet
	 * @return array
	 */
	private function getItemsMissingFromOtherSet( array $set, array $referenceSet )
	{
		return \array_udiff_assoc(
			$set,
			$referenceSet,
			function( $a, $b ) { return $a === $b ? 0 : 1; }
		);
	}

	/**
	 * @param object $owner
	 * @param string $fieldName
	 * @param object $addedObject
	 */
	private function recordDependentEntityRemoval( $owner, $fieldName, $addedObject )
	{
		$this->recordDependentObjectOperation($owner, $fieldName, '-', $addedObject);
	}

	/**
	 * @param object $owner
	 * @param string $fieldName
	 * @param object $addedObject
	 */
	private function recordDependentEntityAddition( $owner, $fieldName, $addedObject )
	{
		$this->recordDependentObjectOperation($owner, $fieldName, '+', $addedObject);
	}

	/**
	 * @param object $owner
	 * @param string $fieldName
	 * @param string $operationKey '-' for deletion, '+' for addition
	 * @param object $addedObject
	 */
	private function recordDependentObjectOperation( $owner, $fieldName, $operationKey, $addedObject )
	{
		$ownerOid = \spl_object_hash($owner);
		if( !isset($this->changeSets[$ownerOid]) )
		{
			$this->addEntityChangeSet($owner, 'upd');
		}
		$this->changeSets[$ownerOid]['changes'][] = [$fieldName, $operationKey, $this->getEntityDescriptor($addedObject)];
	}

	/**
	 * @param object $owner
	 * @param string $ownerFieldName
	 * @param array $changes
	 */
	private function recordDependentObjectChanges( $owner, $ownerFieldName, array $changes )
	{
		$ownerOid = \spl_object_hash($owner);
		if( !isset($this->changeSets[$ownerOid]) )
		{
			$this->addEntityChangeSet($owner, 'upd');
		}

		foreach( $changes as $change )
		{
			$change[0] = $ownerFieldName . ':' . $change[0];
			$this->changeSets[$ownerOid]['changes'][] = $change;
		}
	}

	/**
	 * @param mixed $value
	 * @return scalar
	 */
	private function exportFieldValue( $value )
	{
		switch( true )
		{
			case \is_scalar($value) || \is_null($value):
				return $value;

			case \is_object($value):
				switch( true )
				{
					case $this->isManagedEntity($value):
						return $this->getEntityDescriptor($value);

					case $value instanceof DateTime:
						return $value->format('Y-m-d H:i:s O');

					case \method_exists($value, '__toString'):
						return (string)$value;
				}
				return '[' . \get_class($value) . ']';
		}
		return '[' . \gettype($value) . ']';
	}

	/**
	 * @param object $object
	 * @return bool
	 */
	private function isManagedEntity( $object )
	{
		$state = $this->em->getUnitOfWork()->getEntityState($object, 0);
		return $state == UnitOfWork::STATE_MANAGED || $state == UnitOfWork::STATE_REMOVED;
	}

	/**
	 * @param object $entity
	 * @return string
	 */
	private function getEntityDescriptor( $entity )
	{
		$entityInfo = $this->getEntityInfo($entity);
		return $entityInfo['type'] . ' ' . $entityInfo['id']
			. ($entityInfo['label'] !== null ? ' "' . $entityInfo['label'] . '"' : '');
	}

	/**
	 * @param object $entity
	 * @return array {type: <string>, id: <string|int|null>, label: <string|null>}
	 */
	private function getEntityInfo( $entity )
	{
		$auditInfo = $this->getEntityAuditInfo($entity);
		return [
			'type' => \array_key_exists('type', $auditInfo) ? $auditInfo['type'] : $this->getEntityClassName($entity),
			'id' => \array_key_exists('id', $auditInfo) ? $auditInfo['id'] : $this->getEntityIdString($entity),
			'label' => \array_key_exists('label', $auditInfo) ? $auditInfo['label'] : null
		];
	}

	/**
	 * @param object $entity
	 * @return string
	 */
	private function getEntityClassName( $entity )
	{
		$fullClassName = $entity instanceof Proxy ? \get_parent_class($entity) : \get_class($entity);
		return \strpos($fullClassName, $this->entityRootNamespace) === 0
			? \substr($fullClassName, \strlen($this->entityRootNamespace))
			: $fullClassName;
	}

	/**
	 * @param object $entity
	 * @return string
	 */
	private function getEntityIdString( $entity )
	{
		// ATTENTION: This causes an "undefined index" warning in the UOW for new entities (scheduled for insertion)
		// with DBMS where the ID is generated on insert (as opposed to using a sequence in advance) because
		// the UOW looks for the ID in its ID map without an "isset()" check.
		// Make sure to return an "id" field in the entity's "__getAuditInfo()" method to avoid this.
		return \implode(':', $this->em->getUnitOfWork()->getEntityIdentifier($entity));
	}

	/**
	 * @param object $entity
	 * @return array
	 */
	private function getEntityAuditInfo( $entity )
	{
		return \method_exists($entity, '__getAuditInfo') ? $entity->__getAuditInfo() : [];
	}

	private function logChangeSets()
	{
		foreach( $this->changeSets as $changeSet )
		{
			// There are sometimes update change sets where nothing really changes, e.g. when when the value of a
			// date/time field is replaced by an equivalent DateTime instance which Doctrine sees as a different value.
			if( $changeSet['op'] != 'upd' || $changeSet['changes'] )
			{
				$entityInfo = $this->getEntityInfo($changeSet['obj']);
				$logEntry = new StructuredAuditLogEntry;
				$logEntry->operationClass = self::OPERATION_CLASS_KEY;
				$logEntry->dataFormat = self::DATA_FORMAT_KEY;
				$logEntry->operationType = $changeSet['op'];
				$logEntry->subjectType = $entityInfo['type'];
				$logEntry->subjectId = $entityInfo['id'];
				$logEntry->subjectLabel = $entityInfo['label'];
				$logEntry->setData($changeSet['changes']);
				$this->logger->addEntry($logEntry);
			}
		}
	}
}
