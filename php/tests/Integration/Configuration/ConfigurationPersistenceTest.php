<?php

declare(strict_types=1);

namespace Tests\Integration\Configuration;

use Alxarafe\App\Application\Configuration\ConfigurationConflict;
use Alxarafe\App\Application\Configuration\ConfigureWarehouse;
use Alxarafe\App\Domain\Configuration\HandlingUnitType;
use Alxarafe\App\Domain\Configuration\LocationType;
use Alxarafe\App\Domain\Configuration\LocationTypeHuPolicy;
use Alxarafe\App\Domain\Configuration\WarehouseConfiguration;
use Alxarafe\App\Domain\Configuration\WarehouseFormat;
use Alxarafe\App\Domain\Configuration\WarehouseFormatLocked;
use Alxarafe\App\Domain\Shared\ValueObject\Uuid;
use Alxarafe\App\Infrastructure\Config\Database;
use Alxarafe\App\Infrastructure\Persistence\PdoConfigurationRepository;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

/** Ejecutar mediante bin/php_configuration_test.sh; nunca contra desarrollo. */
final class ConfigurationPersistenceTest extends TestCase
{
    public function testConfigurationRoundTripAndDatabaseGuards(): void
    {
        if (getenv('PHP_CONFIGURATION_TEST') !== '1') {
            self::markTestSkipped('Use bin/php_configuration_test.sh for the dedicated empty databases.');
        }
        $pdo = Database::getConnection();
        $database = $pdo->query('SELECT current_database()');
        self::assertInstanceOf(\PDOStatement::class, $database);
        self::assertContains($database->fetchColumn(), [
            'wms_php_configuration_a_test', 'wms_php_configuration_b_test',
        ]);
        $repository = new PdoConfigurationRepository($pdo);
        $service = new ConfigureWarehouse($repository);
        self::assertSame([], $service->warehouses());
        self::assertSame([], $service->handlingUnitTypes());
        $warehouse = $service->createWarehouse(new WarehouseConfiguration(Uuid::generate(), 'TEST', 'Prueba', new WarehouseFormat(1, 2, 1)));
        $hu = $service->createHandlingUnitType(new HandlingUnitType(Uuid::generate(), 'ARBITRARY', 'Sin significado especial', false));
        $type = $service->createLocationType(new LocationType(Uuid::generate(), $warehouse->id, 'CUSTOM', 'Tipo', 1, true, false));
        $policy = $service->createPolicy($warehouse->id, new LocationTypeHuPolicy($type->id, $hu->id, true, false, true, true));
        self::assertEquals([$warehouse], $service->warehouses());
        self::assertEquals([$hu], $service->handlingUnitTypes());
        self::assertEquals([$type], $service->locationTypes($warehouse->id));
        self::assertEquals([$policy], $service->policies($warehouse->id, $type->id));
        try {
            $service->createWarehouse(new WarehouseConfiguration(Uuid::generate(), 'TEST', 'Duplicado', new WarehouseFormat(1, 2, 1)));
            self::fail('Duplicate must conflict.');
        } catch (ConfigurationConflict) {
            self::assertCount(1, $service->warehouses());
        }

        $format = new WarehouseFormat(2, 3, 2, true, '-', true);
        self::assertEquals($format, $service->changeFormat($warehouse->id, $format)->format);
        $pdo->prepare('INSERT INTO wms_review_v2.aisle (id, warehouse_id, number) VALUES (?, ?, 1)')
            ->execute([Uuid::generate(), $warehouse->id]);
        try {
            $service->changeFormat($warehouse->id, new WarehouseFormat(1, 2, 1));
            self::fail('Changing format after an aisle must fail.');
        } catch (WarehouseFormatLocked) {
            self::assertFalse($pdo->inTransaction(), 'Failed change must roll back.');
            self::assertEquals($format, $repository->warehouse($warehouse->id)?->format);
        }
        self::assertEquals($format, $service->changeFormat($warehouse->id, $format)->format);
        // La restricción SQL sigue funcionando incluso al omitir la aplicación.
        try {
            $pdo->prepare('UPDATE wms_review_v2.warehouse SET bay_digits = 4 WHERE id = ?')->execute([$warehouse->id]);
            self::fail('Database guard must reject a direct update.');
        } catch (PDOException $error) {
            self::assertSame('WMS01', $error->getCode());
        }
        $count = $pdo->query('SELECT count(*) FROM wms_review_v2.location_type_hu_policy');
        self::assertInstanceOf(\PDOStatement::class, $count);
        self::assertSame(1, (int) $count->fetchColumn());

        // Dos conexiones: el alta de la primera calle espera al cambio de formato.
        $other = new PDO(
            sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $_ENV['POSTGRES_HOST'],
                $_ENV['POSTGRES_PORT'],
                $_ENV['POSTGRES_DB'],
            ),
            $_ENV['POSTGRES_USER'],
            $_ENV['POSTGRES_PASSWORD'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        $empty = $service->createWarehouse(new WarehouseConfiguration(Uuid::generate(), 'LOCK', 'Lock', $format));
        $other->exec("SET lock_timeout = '100ms'");
        $pdo->beginTransaction();
        try {
            $pdo->prepare('SELECT id FROM wms_review_v2.warehouse WHERE id = ? FOR UPDATE')->execute([$empty->id]);
            try {
                $other->prepare('INSERT INTO wms_review_v2.aisle (id, warehouse_id, number) VALUES (?, ?, 1)')
                    ->execute([Uuid::generate(), $empty->id]);
                self::fail('Concurrent first aisle must wait for the format lock.');
            } catch (PDOException $error) {
                self::assertSame('55P03', $error->getCode());
            }
        } finally {
            $pdo->rollBack();
        }
        self::assertFalse($repository->hasAisles($empty->id));
        $other->prepare('INSERT INTO wms_review_v2.aisle (id, warehouse_id, number) VALUES (?, ?, 1)')
            ->execute([Uuid::generate(), $empty->id]);
        self::assertTrue($repository->hasAisles($empty->id));
    }
}
