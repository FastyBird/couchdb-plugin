<?php declare(strict_types = 1);

use Ramsey\Uuid\Uuid;

$id = Uuid::uuid4();

return [
	'one'   => [
		$id,
		[
			'id'       => $id->toString(),
			'device'   => 'device_name',
			'property' => 'property_name',
			'value'    => '10',
		],
		'10',
	],
	'two'   => [
		$id,
		[
			'id'       => $id->toString(),
			'device'   => 'device_name',
			'property' => 'property_name',
			'value'    => '1',
		],
		'1',
	],
	'three' => [
		$id,
		[
			'id'       => $id->toString(),
			'device'   => 'device_name',
			'property' => 'property_name',
			'value'    => null,
		],
		null,
	],
	'four'  => [
		$id,
		[
			'id'       => $id->toString(),
			'device'   => 'device_name',
			'property' => 'property_name',
			'value'    => null,
		],
		null,
	],
	'five'  => [
		$id,
		[
			'id'       => $id->toString(),
			'device'   => 'device_name',
			'property' => 'property_name',
			'value'    => '10.45',
		],
		'10.45',
	],
	'six'   => [
		$id,
		[
			'id'       => $id->toString(),
			'device'   => 'device_name',
			'property' => 'property_name',
			'value'    => 'test',
		],
		'test',
	],
	'seven' => [
		$id,
		[
			'id'       => $id->toString(),
			'device'   => 'device_name',
			'property' => 'property_name',
			'format'   => 'one,two,,three',
			'value'    => 'two',
		],
		'two',
	],
	'eight' => [
		$id,
		[
			'id'       => $id->toString(),
			'device'   => 'device_name',
			'property' => 'property_name',
			'format'   => 'hsv',
			'value'    => '255,255,0',
		],
		'255,255,0',
	],
	'nine'  => [
		$id,
		[
			'id'       => $id->toString(),
			'device'   => 'device_name',
			'property' => 'property_name',
			'format'   => 'hsv',
		],
		null,
	],
];
