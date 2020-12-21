<?php declare(strict_types = 1);

/**
 * StatesManager.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
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
 * @method onAfterCreate(States\IState $state)
 * @method onAfterUpdate(States\IState $state, States\IState $old)
 * @method onAfterDelete(States\IState $state)
 */
class StatesManager implements IStatesManager
{

	use Nette\SmartObject;

	private const MAX_RETRIES = 5;

	/** @var Closure[] */
	public array $onAfterCreate = [];

	/** @var Closure[] */
	public array $onAfterUpdate = [];

	/** @var Closure[] */
	public array $onAfterDelete = [];

	/** @var Log\LoggerInterface */
	protected Log\LoggerInterface $logger;

	/** @var int[] */
	private array $retries = [];

	/** @var Connections\ICouchDbConnection */
	private Connections\ICouchDbConnection $dbClient;

	public function __construct(
		Connections\ICouchDbConnection $dbClient,
		?Log\LoggerInterface $logger = null
	) {
		$this->dbClient = $dbClient;

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * {@inheritDoc}
	 */
	public function create(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
		string $class = States\State::class
	): States\IState {
		$values->offsetSet('id', $id->toString());

		try {
			$doc = $this->createDoc($values, $class::CREATE_FIELDS);

			$state = States\StateFactory::create($class, $doc);

		} catch (Throwable $ex) {
			$this->logger->error('[FB:PLUGIN:COUCHDB] Document could not be created', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
				'data'      => [
					'state' => $id->toString(),
				],
			]);

			throw new Exceptions\InvalidStateException('State could not be created', $ex->getCode(), $ex);
		}

		$this->onAfterCreate($state);

		return $state;
	}

	/**
	 * {@inheritDoc}
	 */
	public function update(
		States\IState $state,
		Utils\ArrayHash $values
	): States\IState {
		try {
			$doc = $this->updateDoc($state, $values, $state::UPDATE_FIELDS);

			$updatedState = States\StateFactory::create(get_class($state), $doc);

		} catch (Exceptions\NotUpdatedException $ex) {
			return $state;

		} catch (Throwable $ex) {
			$this->logger->error('[FB:PLUGIN:COUCHDB] Document could not be updated', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
				'data'      => [
					'state' => $state->getId()->toString(),
				],
			]);

			throw new Exceptions\InvalidStateException('State could not be updated', $ex->getCode(), $ex);
		}

		$this->onAfterUpdate($updatedState, $state);

		return $updatedState;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete(
		States\IState $state
	): bool {
		$result = $this->deleteDoc($state->getId()
			->toString());

		if ($result === false) {
			return false;
		}

		$this->onAfterDelete($state);

		return true;
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

			$data->_id = $data->id;

			$this->dbClient->getClient()->storeDoc($data);

			$this->dbClient->getClient()->asCouchDocuments();

			/** @var PHPOnCouch\CouchDocument $doc */
			$doc = $this->dbClient->getClient()->getDoc($data->id);

			return $doc;

		} catch (Throwable $ex) {
			$this->logger->error('[FB:PLUGIN:COUCHDB] Document could not be created', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidStateException('State document could not be created', $ex->getCode(), $ex);
		}
	}

	/**
	 * @param States\IState $state
	 * @param Utils\ArrayHash $values
	 * @param string[] $fields
	 *
	 * @return PHPOnCouch\CouchDocument
	 */
	protected function updateDoc(
		States\IState $state,
		Utils\ArrayHash $values,
		array $fields
	): PHPOnCouch\CouchDocument {
		$doc = $state->getDocument();

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

				$this->updateDoc($state, $values, $fields);
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

			throw new Exceptions\InvalidStateException('State document could not be updated', $ex->getCode(), $ex);

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

			throw new Exceptions\InvalidStateException('State document could not be updated', $ex->getCode(), $ex);
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

}
