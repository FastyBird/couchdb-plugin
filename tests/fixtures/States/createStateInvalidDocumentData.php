<?php declare(strict_types = 1);

use FastyBird\CouchDbStoragePlugin\States;

return [
	'one' => [
		States\Property::class,
		[
			'device'   => 'device-name',
			'property' => 'property-name',
		],
	],
	'two' => [
		States\Property::class,
		[
			'id'       => 'invalid-string',
			'device'   => 'device-name',
			'property' => 'property-name',
		],
	],
];
