<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Configuration;

use Alxarafe\App\Application\Configuration\ConfigurationConflict;
use Alxarafe\App\Application\Configuration\ConfigurationNotFound;
use Alxarafe\App\Application\Configuration\ConfigurationRepository;
use Alxarafe\App\Application\Configuration\ConfigureWarehouse;
use Alxarafe\App\Domain\Configuration\LocationType;
use Alxarafe\App\Domain\Configuration\LocationTypeHuPolicy;
use Alxarafe\App\Domain\Configuration\WarehouseConfiguration;
use Alxarafe\App\Domain\Configuration\WarehouseFormat;
use Alxarafe\App\Domain\Shared\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class ConfigureWarehouseTest extends TestCase
{
    public function testMissingWarehouseDoesNotWriteLocationType(): void
    {
        $repository = $this->createMock(ConfigurationRepository::class);
        $repository->expects(self::once())->method('warehouse')->willReturn(null);
        $repository->expects(self::never())->method('addLocationType');
        $this->expectException(ConfigurationNotFound::class);
        (new ConfigureWarehouse($repository))->createLocationType(new LocationType(Uuid::generate(), Uuid::generate(), 'X', 'X'));
    }

    public function testPolicyCannotUseTypeFromAnotherWarehouse(): void
    {
        $warehouse = new WarehouseConfiguration(Uuid::generate(), 'A', 'A', new WarehouseFormat(1, 2, 1));
        $type = new LocationType(Uuid::generate(), Uuid::generate(), 'X', 'X');
        $repository = $this->createMock(ConfigurationRepository::class);
        $repository->method('warehouse')->willReturn($warehouse);
        $repository->method('locationType')->willReturn($type);
        $repository->expects(self::never())->method('addPolicy');
        $repository->expects(self::never())->method('handlingUnitType');
        $this->expectException(ConfigurationConflict::class);
        (new ConfigureWarehouse($repository))->createPolicy($warehouse->id, new LocationTypeHuPolicy($type->id, Uuid::generate(), true));
    }

    public function testPolicyRequiresExistingHuType(): void
    {
        $warehouse = new WarehouseConfiguration(Uuid::generate(), 'A', 'A', new WarehouseFormat(1, 2, 1));
        $type = new LocationType(Uuid::generate(), $warehouse->id, 'X', 'X');
        $repository = $this->createMock(ConfigurationRepository::class);
        $repository->method('warehouse')->willReturn($warehouse);
        $repository->method('locationType')->willReturn($type);
        $repository->method('handlingUnitType')->willReturn(null);
        $repository->expects(self::never())->method('addPolicy');
        $this->expectException(ConfigurationNotFound::class);
        (new ConfigureWarehouse($repository))->createPolicy($warehouse->id, new LocationTypeHuPolicy($type->id, Uuid::generate(), true));
    }
}
