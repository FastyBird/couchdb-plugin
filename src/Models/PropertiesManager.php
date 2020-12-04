<?php declare(strict_types = 1);

/**
 * PropertiesManager.php
 *
 * @license        More in license.md
 * @copyright      https://fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           03.03.20
 */

namespace FastyBird\CouchDbStoragePlugin\Models;

use Closure;
use Consistence;
use DateTimeInterface;
use FastyBird\CouchDbStoragePlugin\Connections;
use FastyBird\CouchDbStoragePlugin\Exceptions;
use FastyBird\CouchDbStoragePlugin\States;
use FastyBird\DateTimeFactory;
use Nette;
use Nette\Utils;
use PHPOnCouch;
use Psr\Log;
use Ramsey\Uuid;
use stdClass;
use Throwable;

/**
 * Base properties manager
 *
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @method onAfterCreate(States\IProperty $state)
 * @method onAfterUpdate(States\IProperty $state, States\IProperty $old)
 * @method onAfterDelete(States\IProperty $state)
 */
class PropertiesManager implements IPropertiesManager
{

	use Nette\SmartObject;

	private const MAX_RETRIES = 5;

	/** @var Closure[] */
	public $onAfterCreate = [];

	/** @var Closure[] */
	public $onAfterUpdate = [];

	/** @var Closure[] */
	public $onAfterDelete = [];

	/** @var int[] */
	private $retries = [];

	/** @var Connections\ICouchDbConnection */
	private $dbClient;

	/** @var DateTimeFactory\DateTimeFactory */
	private $dateFactory;

	/** @var Log\LoggerInterface */
	protected $logger;

	public function __construct(
		Connections\ICouchDbConnection $dbClient,
		DateTimeFactory\DateTimeFactory $dateFactory,
		?Log\LoggerInterface $logger = null
	) {
		$this->dbClient = $dbClient;

		$this->dateFactory = $dateFactory;

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * {@inheritDoc}
	 */
	public function create(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values
	): States\IProperty {
		$values->offsetSet('id', $id->toString());

		try {
			$doc = $this->createDoc($values, $this->getCreateFields());

			/** @var States\IProperty $state */
			$state = States\StateFactory::create(States\Property::class, $doc);

		} catch (Throwable $ex) {
			$this->logger->error('[FB:PLUGIN:COUCHDB] Document could not be created', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
				'data'      => [
					'property' => $id->toString(),
				],
			]);

			throw new Exceptions\InvalidStateException('Property could not be created', $ex->getCode(), $ex);
		}

		$this->onAfterCreate($state);

		return $state;
	}

	/**
	 * {@inheritDoc}
	 */
	public function update(
		States\IProperty $state,
		Utils\ArrayHash $values
	): States\IProperty {
		try {
			$doc = $this->updateDoc($state, $values, $this->getUpdateFields());

			/** @var States\IProperty $updatedState */
			$updatedState = States\StateFactory::create(States\Property::class, $doc);

		} catch (Exceptions\NotUpdatedException $ex) {
			return $state;

		} catch (Throwable $ex) {
			$this->logger->error('[FB:PLUGIN:COUCHDB] Document could not be updated', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
				'data'      => [
					'property' => $state->getId()->toString(),
				],
			]);

			throw new Exceptions\InvalidStateException('Property could not be updated', $ex->getCode(), $ex);
		}

		$this->onAfterUpdate($updatedState, $state);

		return $updatedState;
	}

	/**
	 * {@inheritDoc}
	 */
	public function updateState(
		States\IProperty $state,
		Utils\ArrayHash $values
	): States\IProperty {
		try {
			$doc = $this->updateDoc(
				$state,
				$values,
				[
					'value',
					'expected',
					'pending',
				]
			);

			/** @var States\IProperty $updatedState */
			$updatedState = States\StateFactory::create(States\Property::class, $doc);

		} catch (Exceptions\NotUpdatedException $ex) {
			return $state;

		} catch (Throwable $ex) {
			$this->logger->error('[FB:PLUGIN:COUCHDB] Document could not be updated', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
				'data'      => [
					'property' => $state->getId()->toString(),
				],
			]);

			throw new Exceptions\InvalidStateException('Property could not be updated', $ex->getCode(), $ex);
		}

		$this->onAfterUpdate($updatedState, $state);

		return $state;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete(
		States\IProperty $state
	): bool {
		$result = $this->deleteDoc($state->getId()->toString());

		if ($result === false) {
			return false;
		}

		$this->onAfterDelete($state);

		return true;
	}

	/**
	 * @param Utils\ArrayHash $values
	 * @param mixed[] $fields
	 *
	 * @return PHPOnCouch\CouchDocument
	 */
	protected function createDoc(
		Utils\ArrayHash $values,
		array $fields
	): PHPOnCouch\CouchDocument {
		try {
			// Initialize structure
			$data = new stdClass();

			foreach ($fields as $field => $default) {
				$value = $default;

				if (is_numeric($field)) {
					$field = $default;

					// If default is not defined => field is required
					if (!$values->offsetExists($field)) {
						throw new Exceptions\InvalidArgumentException(sprintf('Value for key "%s" is required', $field));
					}

					$value = $values->offsetGet($field);

				} elseif ($values->offsetExists($field)) {
					if ($values->offsetGet($field) !== null) {
						$value = $values->offsetGet($field);

						if ($value instanceof DateTimeInterface) {
							$value = $value->format(DATE_ATOM);

						} elseif ($value instanceof Utils\ArrayHash) {
							$value = (array) $value;

						} elseif ($value instanceof Consistence\Enum\Enum) {
							$value = $value->getValue();

						} elseif (is_object($value)) {
							$value = (string) $value;
						}

					} else {
						$value = null;
					}
				}

				$data->{$field} = $value;
			}

			$data->value = null;
			$data->expected = null;
			$data->pending = false;

			$data->created = $this->dateFactory->getNow()->format(DATE_ATOM);
			$data->updated = null;

			$data->_id = $data->id;

			$this->dbClient->getClient()->storeDoc($data);

			$this->dbClient->getClient()->asCouchDocuments();

			/** @var PHPOnCouch\CouchDocument $doc */
			$doc = $this->dbClient->getClient()->getDoc($data->id);

			return $doc;

		} catch (Throwable $ex) {
			var_dump($ex->getMessage());
			$this->logger->error('[FB:PLUGIN:COUCHDB] Document could not be created', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidStateException('Property document could not be created', $ex->getCode(), $ex);
		}
	}

	/**
	 * @param States\IProperty $property
	 * @param Utils\ArrayHash $values
	 * @param string[] $fields
	 *
	 * @return PHPOnCouch\CouchDocument
	 */
	protected function updateDoc(
		States\IProperty $property,
		Utils\ArrayHash $values,
		array $fields
	): PHPOnCouch\CouchDocument {
		$doc = $property->getDocument();

		try {
			$doc->setAutocommit(false);

			$isUpdated = false;

			foreach ($fields as $field) {
				if ($values->offsetExists($field)) {
					$value = $values->offsetGet($field);

					if ($value instanceof DateTimeInterface) {
						$value = $value->format(DATE_ATOM);

					} elseif ($value instanceof Utils\ArrayHash) {
						$value = (array) $value;

					} elseif ($value instanceof Consistence\Enum\Enum) {
						$value = $value->getValue();

					} elseif (is_object($value)) {
						$value = (string) $value;
					}

					if ($doc->get($field) !== $value) {
						$doc->set($field, $value);

						$isUpdated = true;
					}
				}
			}

			// Commit doc only if is updated
			if (!$isUpdated) {
				throw new Exceptions\NotUpdatedException('State is not updated');
			}

			$doc->set('updated', $this->dateFactory->getNow()->format(DATE_ATOM));

			// Commit changes into database
			$doc->record();

			unset($this->retries[$doc->id()]);

			return $doc;

		} catch (PHPOnCouch\Exceptions\CouchConflictException $ex) {
			if (
				!isset($this->retries[$doc->id()])
				|| $this->retries[$doc->id()] <= self::MAX_RETRIES
			) {
				if (!isset($this->retries[$doc->id()])) {
					$this->retries[$doc->id()] = 0;
				}

				$this->retries[$doc->id()]++;

				$this->updateDoc($property, $values, $fields);
			}

			$this->logger->error('[FB:PLUGIN:COUCHDB] Document could not be updated', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
				'document'  => [
					'id' => $doc->id(),
				],
			]);

			throw new Exceptions\InvalidStateException('Property document could not be updated', $ex->getCode(), $ex);

		} catch (Exceptions\NotUpdatedException $ex) {
			throw $ex;

		} catch (Throwable $ex) {
			$this->logger->error('[FB:PLUGIN:COUCHDB] Document could not be updated', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
				'document'  => [
					'id' => $doc->id(),
				],
			]);

			throw new Exceptions\InvalidStateException('Property document could not be updated', $ex->getCode(), $ex);
		}
	}

	/**
	 * @param string $id
	 *
	 * @return bool
	 */
	protected function deleteDoc(
		string $id
	): bool {
		try {
			$doc = $this->loadDoc($id);

			// Document is already deleted
			if ($doc === null) {
				return true;
			}

			$this->dbClient->getClient()->deleteDoc($doc);

			return true;

		} catch (Throwable $ex) {
			$this->logger->error('[FB:PLUGIN:COUCHDB] Document could not be deleted', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
				'document'  => [
					'id' => $id,
				],
			]);
		}

		return false;
	}

	/**
	 * @param string $id
	 *
	 * @return PHPOnCouch\CouchDocument
	 */
	protected function loadDoc(
		string $id
	): ?PHPOnCouch\CouchDocument {
		try {
			$this->dbClient->getClient()->asCouchDocuments();

			/** @var PHPOnCouch\CouchDocument $doc */
			$doc = $this->dbClient->getClient()->getDoc($id);

			return $doc;

		} catch (PHPOnCouch\Exceptions\CouchNotFoundException $ex) {
			return null;

		} catch (Throwable $ex) {
			$this->logger->error('[FB:PLUGIN:COUCHDB] Document could not be deleted', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
				'document'  => [
					'id' => $id,
				],
			]);

			throw new Exceptions\InvalidStateException('Document could not found.');
		}
	}

	/**
	 * @return mixed[]
	 */
	protected function getCreateFields(): array
	{
		return [
			0          => 'id',
			'value'    => null,
			'expected' => null,
			'pending'  => false,
		];
	}

	/**
	 * @return string[]
	 */
	protected function getUpdateFields(): array
	{
		return [
			'value',
			'expected',
			'pending',
		];
	}

}
