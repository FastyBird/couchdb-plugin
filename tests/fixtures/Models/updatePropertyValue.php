<?php declare(strict_types = 1);

use Ramsey\Uuid;

$id = Uuid\Uuid::uuid4()->toString();

return [
	'one'   => [
		[
			'settable' => true,
		],
		[
			'id'       => $id,
			'value'    => null,
			'expected' => null,
			'pending'  => false,
		],
		[
			'id'       => $id,
			'value'    => null,
			'expected' => null,
			'pending'  => false,
		],
	],
	'two'   => [
		[
			'settable'  => true,
			'queryable' => true,
		],
		[
			'id'       => $id,
			'value'    => null,
			'expected' => null,
			'pending'  => false,
		],
		[
			'id'       => $id,
			'value'    => null,
			'expected' => null,
			'pending'  => false,
		],
	],
	'three' => [
		[
			'queryable' => true,
		],
		[
			'id'       => $id,
			'value'    => null,
			'expected' => null,
			'pending'  => false,
		],
		[
			'id'       => $id,
			'value'    => null,
			'expected' => null,
			'pending'  => false,
		],
	],
	'four'  => [
		[
			'queryable' => true,
			'value'     => 10.33,
		],
		[
			'id'       => $id,
			'value'    => null,
			'expected' => null,
			'pending'  => false,
		],
		[
			'id'       => $id,
			'value'    => '10.33',
			'expected' => null,
			'pending'  => false,
		],
	],
];
