<?php declare(strict_types = 1);

/**
 * PropertyUpdatedHandler.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     Events
 * @since          0.1.0
 *
 * @date           03.03.20
 */

namespace FastyBird\CouchDbStoragePlugin\Events;

use FastyBird\ApplicationExchange\Publisher as ApplicationExchangePublisher;
use FastyBird\CouchDbStoragePlugin\Exceptions;
use FastyBird\CouchDbStoragePlugin\States;
use FastyBird\DevicesModule;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\DevicesModule\Queries as DevicesModuleQueries;
use Nette;

/**
 * State property created|changed handler
 *
 * @package         FastyBird:CouchDbStoragePlugin!
 * @subpackage      Events
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class PropertyStateUpdatedHandler
{

	use Nette\SmartObject;

	/** @var DevicesModuleModels\Devices\Properties\IPropertyRepository */
	private DevicesModuleModels\Devices\Properties\IPropertyRepository $devicePropertyRepository;

	/** @var DevicesModuleModels\Channels\Properties\IPropertyRepository */
	private DevicesModuleModels\Channels\Properties\IPropertyRepository $channelPropertyRepository;

	/** @var ApplicationExchangePublisher\IPublisher */
	private ApplicationExchangePublisher\IPublisher $publisher;

	public function __construct(
		DevicesModuleModels\Devices\Properties\IPropertyRepository $devicePropertyRepository,
		DevicesModuleModels\Channels\Properties\IPropertyRepository $channelPropertyRepository,
		ApplicationExchangePublisher\IPublisher $publisher
	) {
		$this->devicePropertyRepository = $devicePropertyRepository;
		$this->channelPropertyRepository = $channelPropertyRepository;

		$this->publisher = $publisher;
	}

	/**
	 * @param States\IProperty $state
	 * @param States\IProperty $previous
	 *
	 * @return void
	 */
	public function __invoke(
		States\IProperty $state,
		States\IProperty $previous
	): void {
		$findProperty = new DevicesModuleQueries\FindDevicePropertiesQuery();
		$findProperty->byId($state->getId());

		// Try to find device property...
		$property = $this->devicePropertyRepository->findOneBy($findProperty);

		// ...state is not for device or state id is invalid...
		if ($property === null) {
			$findProperty = new DevicesModuleQueries\FindChannelPropertiesQuery();
			$findProperty->byId($state->getId());

			// ...try to find channel property
			$property = $this->channelPropertyRepository->findOneBy($findProperty);
		}

		if ($property === null) {
			throw new Exceptions\InvalidArgumentException('Property for provided state could not be found.');
		}

		if (array_key_exists(get_class($property), DevicesModule\Constants::MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING)) {
			$routingKey = DevicesModule\Constants::MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING[get_class($property)];

		} else {
			throw new Exceptions\InvalidArgumentException('Provided state is not supported by RabbitMQ exchange publisher.');
		}

		$this->publisher->publish($routingKey, array_merge(
			$state->toArray(),
			[
				'previous_value' => $previous->getValue(),
			],
			$property->toArray()
		));
	}

}
