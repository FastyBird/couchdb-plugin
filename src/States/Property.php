<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     States
 * @since          0.1.0
 *
 * @date           03.03.20
 */

namespace FastyBird\CouchDbStoragePlugin\States;

use DateTimeImmutable;
use DateTimeInterface;
use Nette;

/**
 * Property state
 *
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Property extends State implements IProperty
{

	use Nette\SmartObject;

	/** @var string|null */
	private $value = null;

	/** @var string|null */
	private $expected = null;

	/** @var bool */
	private $pending = false;

	/** @var string|null */
	private $created = null;

	/** @var string|null */
	private $updated = null;

	/**
	 * {@inheritDoc}
	 */
	public function getCreated(): ?DateTimeInterface
	{
		return $this->created !== null ? new DateTimeImmutable($this->created) : null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setCreated(?string $created): void
	{
		$this->created = $created;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getUpdated(): ?DateTimeInterface
	{
		return $this->updated !== null ? new DateTimeImmutable($this->updated) : null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setUpdated(?string $updated): void
	{
		$this->updated = $updated;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge([
			'value'    => $this->getValue(),
			'expected' => $this->getExpected(),
			'pending'  => $this->isPending(),
		], parent::toArray());
	}

	/**
	 * {@inheritDoc}
	 */
	public function getValue(): ?string
	{
		return $this->value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setValue(?string $value): void
	{
		$this->value = $value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getExpected(): ?string
	{
		return $this->expected;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setExpected(?string $expected): void
	{
		$this->expected = $expected;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isPending(): bool
	{
		return $this->pending;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setPending(bool $pending): void
	{
		$this->pending = $pending;
	}

}
