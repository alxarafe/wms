<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Configuration;

use Alxarafe\App\Domain\Configuration\HandlingUnitType;
use Alxarafe\App\Domain\Configuration\LocationType;
use Alxarafe\App\Domain\Configuration\LocationTypeHuPolicy;
use Alxarafe\App\Domain\Configuration\WarehouseConfiguration;
use Alxarafe\App\Domain\Configuration\WarehouseFormat;

/** Puerto de persistencia de la configuración inicial; sin detalles SQL. */
interface ConfigurationRepository
{
    public function addWarehouse(WarehouseConfiguration $warehouse): void;
    public function warehouse(string $id): ?WarehouseConfiguration;
    /** @return list<WarehouseConfiguration> */
    public function warehouses(): array;
    public function addHandlingUnitType(HandlingUnitType $type): void;
    public function handlingUnitType(string $id): ?HandlingUnitType;
    /** @return list<HandlingUnitType> */
    public function handlingUnitTypes(): array;
    public function addLocationType(LocationType $type): void;
    public function locationType(string $id): ?LocationType;
    /** @return list<LocationType> */
    public function locationTypes(string $warehouseId): array;
    public function addPolicy(LocationTypeHuPolicy $policy): void;
    /** @return list<LocationTypeHuPolicy> */
    public function policies(string $locationTypeId): array;
    /**
     * Ejecuta la operación con exclusión sobre el almacén y atomicidad.
     * @param callable(): WarehouseConfiguration $operation
     */
    public function withWarehouseLock(string $id, callable $operation): WarehouseConfiguration;
    public function hasAisles(string $warehouseId): bool;
    public function saveFormat(string $warehouseId, WarehouseFormat $format): void;
}
