<?php declare(strict_types = 1);

use FastyBird\CouchDbStoragePlugin\States;
use Ramsey\Uuid\Uuid;

return [
	'one' => [
		States\Property::class,
		[
			'id' => Uuid::uuid4()
				->toString(),
		],
	],
	'two' => [
		States\Property::class,
		[
			'id' => Uuid::uuid4()
				->toString(),
		],
	],
];
