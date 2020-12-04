<?php declare(strict_types = 1);

return [
	'one'   => [
		[
			'device'   => 'device_name',
			'property' => 'property_name',
			'format'   => [3.0, 30.0],
			'parent'   => null,
		],
		[
			'value'    => null,
			'expected' => null,
			'pending'  => false,
		],
		[
			'value'    => null,
			'expected' => null,
			'pending'  => false,
		],
	],
	'two'   => [
		[
			'device'   => 'device_name',
			'property' => 'property_name',
			'format'   => [3.0, 30.0],
			'settable' => true,
			'parent'   => null,
		],
		[
			'value'    => null,
			'expected' => null,
			'pending'  => false,
		],
		[
			'value'    => null,
			'expected' => null,
			'pending'  => false,
		],
	],
	'three' => [
		[
			'device'   => 'device_name',
			'property' => 'property_name',
			'format'   => [3.0, 30.0],
			'settable' => true,
			'value'    => 3.55,
			'parent'   => null,
		],
		[
			'value'    => null,
			'expected' => null,
			'pending'  => false,
		],
		[
			'value'    => null,
			'expected' => null,
			'pending'  => false,
		],
	],
];
