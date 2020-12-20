<?php declare(strict_types = 1);

/**
 * EntitiesSubscriber.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           22.03.20
 */

namespace FastyBird\CouchDbStoragePlugin\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\CouchDbStoragePlugin\Models;
use FastyBird\Database\Entities as DatabaseEntities;
use FastyBird\DevicesModule\Entities as DevicesModuleEntities;
use Nette;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class EntitiesSubscriber implements Common\EventSubscriber
{

	private const ACTION_CREATED = 'created';
	private const ACTION_UPDATED = 'updated';
	private const ACTION_DELETED = 'deleted';

	use Nette\SmartObject;

	/** @var Models\IPropertiesManager */
	private Models\IPropertiesManager $propertiesStatesManager;

	/** @var Models\IPropertyRepository */
	private Models\IPropertyRepository $propertyStateRepository;

	/** @var ORM\EntityManagerInterface */
	private ORM\EntityManagerInterface $entityManager;

	public function __construct(
		Models\IPropertiesManager $propertiesStatesManager,
		Models\IPropertyRepository $propertyStateRepository,
		ORM\EntityManagerInterface $entityManager
	) {
		$this->propertiesStatesManager = $propertiesStatesManager;
		$this->propertyStateRepository = $propertyStateRepository;

		$this->entityManager = $entityManager;
	}

	/**
	 * Register events
	 *
	 * @return string[]
	 */
	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::onFlush,
			ORM\Events::postPersist,
			ORM\Events::postUpdate,
		];
	}

	/**
	 * @param ORM\Event\LifecycleEventArgs $eventArgs
	 *
	 * @return void
	 */
	public function postPersist(ORM\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (
			!$entity instanceof DevicesModuleEntities\Devices\Properties\IProperty &&
			!$entity instanceof DevicesModuleEntities\Channels\Properties\IProperty
		) {
			return;
		}

		$this->processEntityAction($entity, self::ACTION_CREATED);
	}

	/**
	 * @param ORM\Event\LifecycleEventArgs $eventArgs
	 *
	 * @return void
	 */
	public function postUpdate(ORM\Event\LifecycleEventArgs $eventArgs): void
	{
		$uow = $this->entityManager->getUnitOfWork();

		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Get changes => should be already computed here (is a listener)
		$changeset = $uow->getEntityChangeSet($entity);

		// If we have no changes left => don't create revision log
		if (count($changeset) === 0) {
			return;
		}

		// Check for valid entity
		if (
			(
				!$entity instanceof DevicesModuleEntities\Devices\Properties\IProperty &&
				!$entity instanceof DevicesModuleEntities\Channels\Properties\IProperty
			) || $uow->isScheduledForDelete($entity)
		) {
			return;
		}

		$this->processEntityAction($entity, self::ACTION_UPDATED);
	}

	/**
	 * @return void
	 */
	public function onFlush(): void
	{
		$uow = $this->entityManager->getUnitOfWork();

		$processedEntities = [];

		$processEntities = [];

		foreach ($uow->getScheduledEntityDeletions() as $entity) {
			// Doctrine is fine deleting elements multiple times. We are not.
			$hash = $this->getHash($entity, $uow->getEntityIdentifier($entity));

			if (in_array($hash, $processedEntities, true)) {
				continue;
			}

			$processedEntities[] = $hash;

			// Check for valid entity
			if (
				!$entity instanceof DevicesModuleEntities\Devices\Properties\IProperty &&
				!$entity instanceof DevicesModuleEntities\Channels\Properties\IProperty
			) {
				continue;
			}

			$processEntities[] = $entity;
		}

		foreach ($processEntities as $entity) {
			$this->processEntityAction($entity, self::ACTION_DELETED);
		}
	}

	/**
	 * @param DatabaseEntities\IEntity $entity
	 * @param string $action
	 *
	 * @return void
	 */
	private function processEntityAction(DatabaseEntities\IEntity $entity, string $action): void
	{
		if (
			$entity instanceof DevicesModuleEntities\Devices\Properties\IProperty ||
			$entity instanceof DevicesModuleEntities\Channels\Properties\IProperty
		) {
			$state = $this->propertyStateRepository->findOne($entity->getId());

			switch ($action) {
				case self::ACTION_CREATED:
				case self::ACTION_UPDATED:
					if ($state === null) {
						$this->propertiesStatesManager->create($entity->getId(), Nette\Utils\ArrayHash::from($entity->toArray()));
					}
					break;

				case self::ACTION_DELETED:
					if ($state !== null) {
						$this->propertiesStatesManager->delete($state);
					}
					break;
			}
		}
	}

	/**
	 * @param DatabaseEntities\IEntity $entity
	 * @param mixed[] $identifier
	 *
	 * @return string
	 */
	private function getHash(DatabaseEntities\IEntity $entity, array $identifier): string
	{
		return implode(
			' ',
			array_merge(
				[$this->getRealClass(get_class($entity))],
				$identifier
			)
		);
	}

	/**
	 * @param string $class
	 *
	 * @return string
	 */
	private function getRealClass(string $class): string
	{
		$pos = strrpos($class, '\\' . Persistence\Proxy::MARKER . '\\');

		if ($pos === false) {
			return $class;
		}

		return substr($class, $pos + Persistence\Proxy::MARKER_LENGTH + 2);
	}

}
