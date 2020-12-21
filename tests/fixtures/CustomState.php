<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use FastyBird\CouchDbStoragePlugin\States;

class CustomState extends States\State
{

	public const CREATE_FIELDS = [
		0         => 'id',
		1         => 'value',
		'created' => false,
	];

	public const UPDATE_FIELDS = [
		'value',
		'updated',
	];

	/** @var string|null */
	private ?string $value = null;

	/** @var string|null */
	private ?string $created = null;

	/** @var string|null */
	private ?string $updated = null;

	/**
	 * @return string|null
	 */
	public function getValue(): ?string
	{
		return $this->value;
	}

	/**
	 * @param string|null $value
	 *
	 * @return void
	 */
	public function setValue(?string $value): void
	{
		$this->value = $value;
	}

	/**
	 * @return DateTimeInterface|null
	 *
	 * @throws Exception
	 */
	public function getCreated(): ?DateTimeInterface
	{
		return $this->created !== null ? new DateTimeImmutable($this->created) : null;
	}

	/**
	 * @param string|null $created
	 *
	 * @return void
	 */
	public function setCreated(?string $created): void
	{
		$this->created = $created;
	}

	/**
	 * @return DateTimeInterface|null
	 *
	 * @throws Exception
	 */
	public function getUpdated(): ?DateTimeInterface
	{
		return $this->updated !== null ? new DateTimeImmutable($this->updated) : null;
	}

	/**
	 * @param string|null $updated
	 *
	 * @return void
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
			'value'   => $this->getValue(),
			'created' => $this->getCreated() !== null ? $this->getCreated()->format(DATE_ATOM) : null,
			'updated' => $this->getUpdated() !== null ? $this->getUpdated()->format(DATE_ATOM) : null,
		], parent::toArray());
	}

}
