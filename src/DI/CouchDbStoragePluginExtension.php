<?php declare(strict_types = 1);

/**
 * CouchDbStoragePluginExtension.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           04.12.20
 */

namespace FastyBird\CouchDbStoragePlugin\DI;

use FastyBird\CouchDbStoragePlugin\Connections;
use FastyBird\CouchDbStoragePlugin\Events;
use FastyBird\CouchDbStoragePlugin\Models;
use FastyBird\CouchDbStoragePlugin\Subscribers;
use Nette;
use Nette\DI;
use Nette\Schema;
use stdClass;

/**
 * CouchDB state storage extension container
 *
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class CouchDbStoragePluginExtension extends DI\CompilerExtension
{

	/**
	 * @param Nette\Configurator $config
	 * @param string $extensionName
	 *
	 * @return void
	 */
	public static function register(
		Nette\Configurator $config,
		string $extensionName = 'fbCouchDbStoragePlugin'
	): void {
		$config->onCompile[] = function (
			Nette\Configurator $config,
			DI\Compiler $compiler
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new CouchDbStoragePluginExtension());
		};
	}

	/**
	 * {@inheritDoc}
	 */
	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'connection' => Schema\Expect::structure([
				'database' => Schema\Expect::string()
					->default('state_storage'),
				'host'     => Schema\Expect::string()
					->default('127.0.0.1'),
				'port'     => Schema\Expect::int(5672),
				'username' => Schema\Expect::string('guest')
					->nullable(),
				'password' => Schema\Expect::string('guest')
					->nullable(),
			]),
		]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		/** @var stdClass $configuration */
		$configuration = $this->getConfig();

		$builder->addDefinition(null)
			->setType(Connections\CouchDbConnection::class)
			->setArguments([
				'database' => $configuration->connection->database,
				'host'     => $configuration->connection->host,
				'port'     => $configuration->connection->port,
				'username' => $configuration->connection->username,
				'password' => $configuration->connection->password,
			]);

		$builder->addDefinition(null)
			->setType(Models\PropertiesManager::class);

		$builder->addDefinition(null)
			->setType(Models\PropertyRepository::class);

		$builder->addDefinition(null)
			->setType(Subscribers\EntitiesSubscriber::class);

		$builder->addDefinition('event.propertyState')
			->setType(Events\PropertyStateUpdatedHandler::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		$propertiesManagerServiceName = $builder->getByType(Models\PropertiesManager::class);

		if ($propertiesManagerServiceName !== null) {
			/** @var DI\Definitions\ServiceDefinition $propertiesManagerService */
			$propertiesManagerService = $builder->getDefinition($propertiesManagerServiceName);

			$propertiesManagerService
				->addSetup('$onAfterUpdate[]', ['@' . $this->prefix('event.propertyState')]);
		}
	}

}
