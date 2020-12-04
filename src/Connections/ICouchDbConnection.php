<?php declare(strict_types = 1);

/**
 * ICouchDbConnection.php
 *
 * @license        More in license.md
 * @copyright      https://fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     Connections
 * @since          0.1.0
 *
 * @date           03.03.20
 */

namespace FastyBird\CouchDbStoragePlugin\Connections;

use PHPOnCouch;

/**
 * Couch DB connection configuration interface
 *
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     Connections
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface ICouchDbConnection
{

	/**
	 * @return string
	 */
	public function getHost(): string;

	/**
	 * @return int
	 */
	public function getPort(): int;

	/**
	 * @return string|null
	 */
	public function getUsername(): ?string;

	/**
	 * @return string|null
	 */
	public function getPassword(): ?string;

	/**
	 * @return string
	 */
	public function getDatabase(): string;

	/**
	 * @return PHPOnCouch\CouchClient
	 */
	public function getClient(): PHPOnCouch\CouchClient;

}
