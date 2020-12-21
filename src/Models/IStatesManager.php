<?php declare(strict_types = 1);

/**
 * IStatesManager.php
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

use FastyBird\CouchDbStoragePlugin\States;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * Base states manager interface
 *
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IStatesManager
{

	/**
	 * @param Uuid\UuidInterface $id
	 * @param Utils\ArrayHash $values
	 * @param string $class
	 *
	 * @return States\IState
	 */
	public function create(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
		string $class = States\State::class
	): States\IState;

	/**
	 * @param States\IState $state
	 * @param Utils\ArrayHash $values
	 *
	 * @return States\IState
	 */
	public function update(
		States\IState $state,
		Utils\ArrayHash $values
	): States\IState;

	/**
	 * @param States\IState $state
	 *
	 * @return bool
	 */
	public function delete(
		States\IState $state
	): bool;

}
