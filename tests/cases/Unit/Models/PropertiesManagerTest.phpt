<?php declare(strict_types = 1);

namespace Tests\Cases;

use Consistence;
use DateTimeImmutable;
use FastyBird\CouchDbStoragePlugin\Connections;
use FastyBird\CouchDbStoragePlugin\Models;
use FastyBird\CouchDbStoragePlugin\States;
use FastyBird\DateTimeFactory;
use Mockery;
use Nette\Utils;
use Ninjify\Nunjuck\TestCase\BaseMockeryTestCase;
use PHPOnCouch;
use Psr\Log;
use Ramsey\Uuid;
use stdClass;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class PropertiesManagerTest extends BaseMockeryTestCase
{

	/**
	 * @param mixed[] $data
	 * @param mixed[] $dbData
	 * @param mixed[] $expected
	 *
	 * @dataProvider ./../../../fixtures/Models/createPropertyValue.php
	 */
	public function testCreateEntity(array $data, array $dbData, array $expected): void
	{
		$id = Uuid\Uuid::uuid4();

		$now = new DateTimeImmutable();

		$dateFactory = Mockery::mock(DateTimeFactory\DateTimeFactory::class);
		$dateFactory
			->shouldReceive('getNow')
			->andReturn($now);

		$couchDbClient = Mockery::mock(PHPOnCouch\CouchClient::class);
		$couchDbClient
			->shouldReceive('storeDoc')
			->withArgs(function (stdClass $create) use ($dbData, $now): bool {
				foreach ($dbData as $key => $value) {
					Assert::equal($value, $create->$key);
				}

				Assert::equal($now->format(DATE_ATOM), $create->created);
				Assert::null($create->updated);

				return true;
			})
			->getMock()
			->shouldReceive('asCouchDocuments')
			->getMock()
			->shouldReceive('getDoc')
			->andReturnUsing(function () use ($dbData, $id): PHPOnCouch\CouchDocument {
				$dbData['id'] = $id->toString();

				/** @var Mockery\MockInterface|PHPOnCouch\CouchDocument $document */
				$document = Mockery::mock(PHPOnCouch\CouchDocument::class);
				$document
					->shouldReceive('id')
					->andReturn($dbData['id'])
					->getMock()
					->shouldReceive('get')
					->andReturnUsing(function ($key) use ($dbData) {
						return $dbData[$key];
					})
					->getMock()
					->shouldReceive('getKeys')
					->andReturn(array_keys($dbData));

				return $document;
			});

		$couchDbConnection = Mockery::mock(Connections\ICouchDbConnection::class);
		$couchDbConnection
			->shouldReceive('getClient')
			->andReturn($couchDbClient);

		$logger = Mockery::mock(Log\LoggerInterface::class);

		$manager = new Models\PropertiesManager($couchDbConnection, $dateFactory, $logger);

		$state = $manager->create($id, Utils\ArrayHash::from($data));

		$expected['id'] = $id->toString();

		Assert::type(States\Property::class, $state);
		Assert::equal($expected, $state->toArray());
	}

	/**
	 * @param mixed[] $data
	 * @param mixed[] $originalData
	 * @param mixed[] $expected
	 *
	 * @dataProvider ./../../../fixtures/Models/updatePropertyValue.php
	 */
	public function testUpdateEntity(array $data, array $originalData, array $expected): void
	{
		$now = new DateTimeImmutable();

		$dateFactory = Mockery::mock(DateTimeFactory\DateTimeFactory::class);
		$dateFactory
			->shouldReceive('getNow')
			->andReturn($now);

		/** @var Mockery\MockInterface|PHPOnCouch\CouchDocument $document */
		$document = Mockery::mock(PHPOnCouch\CouchDocument::class);
		$document
			->shouldReceive('setAutocommit')
			->getMock()
			->shouldReceive('get')
			->andReturnUsing(function ($key) use (&$originalData) {
				return $originalData[$key];
			})
			->getMock()
			->shouldReceive('set')
			->withArgs(function ($key, $value) use ($data, $now, &$originalData): bool {
				if ($key === 'updated') {
					Assert::equal($now->format(DATE_ATOM), $value);

				} elseif ($data[$key] instanceof Consistence\Enum\Enum) {
					Assert::equal((string) $data[$key], $value);

				} else {
					Assert::equal($data[$key], $value);
				}

				$originalData[$key] = $value;

				return true;
			})
			->getMock()
			->shouldReceive('record')
			->getMock()
			->shouldReceive('getKeys')
			->andReturn(array_keys($originalData))
			->getMock()
			->shouldReceive('id')
			->andReturn($originalData['id']);

		$couchDbClient = Mockery::mock(PHPOnCouch\CouchClient::class);
		$couchDbClient
			->shouldReceive('asCouchDocuments');

		$couchDbConnection = Mockery::mock(Connections\ICouchDbConnection::class);
		$couchDbConnection
			->shouldReceive('getClient')
			->andReturn($couchDbClient);

		$logger = Mockery::mock(Log\LoggerInterface::class);

		$manager = new Models\PropertiesManager($couchDbConnection, $dateFactory, $logger);

		$original = new States\Property($originalData['id'], $document);

		$state = $manager->update($original, Utils\ArrayHash::from($data));

		Assert::type(States\Property::class, $state);
		Assert::equal($expected, $state->toArray());
	}

	public function testDeleteEntity(): void
	{
		$originalData = [
			'id'       => Uuid\Uuid::uuid4()->toString(),
			'device'   => 'device_name',
			'property' => 'property_name',
		];

		$now = new DateTimeImmutable();

		$dateFactory = Mockery::mock(DateTimeFactory\DateTimeFactory::class);
		$dateFactory
			->shouldReceive('getNow')
			->andReturn($now);

		/** @var Mockery\MockInterface|PHPOnCouch\CouchDocument $document */
		$document = Mockery::mock(PHPOnCouch\CouchDocument::class);

		$couchDbClient = Mockery::mock(PHPOnCouch\CouchClient::class);
		$couchDbClient
			->shouldReceive('asCouchDocuments')
			->times(1)
			->getMock()
			->shouldReceive('getDoc')
			->withArgs([$originalData['id']])
			->andReturn($document)
			->times(1)
			->getMock()
			->shouldReceive('deleteDoc')
			->withArgs([$document])
			->times(1);

		$couchDbConnection = Mockery::mock(Connections\ICouchDbConnection::class);
		$couchDbConnection
			->shouldReceive('getClient')
			->andReturn($couchDbClient);

		$logger = Mockery::mock(Log\LoggerInterface::class);

		$manager = new Models\PropertiesManager($couchDbConnection, $dateFactory, $logger);

		$original = new States\Property($originalData['id'], $document);

		Assert::true($manager->delete($original));
	}

}

$test_case = new PropertiesManagerTest();
$test_case->run();
