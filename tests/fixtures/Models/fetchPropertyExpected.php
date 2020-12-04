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
			'expected' => '10',
		],
		'10',
	],
	'two'   => [
		$id,
		[
			'id'       => $id->toString(),
			'device'   => 'device_name',
			'property' => 'property_name',
			'expected' => '1',
		],
		'1',
	],
	'three' => [
		$id,
		[
			'id'       => $id->toString(),
			'device'   => 'device_name',
			'property' => 'property_name',
			'expected' => null,
		],
		null,
	],
	'four'  => [
		$id,
		[
			'id'       => $id->toString(),
			'device'   => 'device_name',
			'property' => 'property_name',
			'expected' => null,
		],
		null,
	],
	'five'  => [
		$id,
		[
			'id'       => $id->toString(),
			'device'   => 'device_name',
			'property' => 'property_name',
			'expected' => '10.45',
		],
		'10.45',
	],
	'six'   => [
		$id,
		[
			'id'       => $id->toString(),
			'device'   => 'device_name',
			'property' => 'property_name',
			'expected' => 'test',
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
			'expected' => 'two',
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
			'expected' => '255,255,0',
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
