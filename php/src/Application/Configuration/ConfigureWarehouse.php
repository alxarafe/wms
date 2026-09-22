<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Configuration;

use Alxarafe\App\Domain\Configuration\HandlingUnitType;
use Alxarafe\App\Domain\Configuration\LocationType;
use Alxarafe\App\Domain\Configuration\LocationTypeHuPolicy;
use Alxarafe\App\Domain\Configuration\WarehouseConfiguration;
use Alxarafe\App\Domain\Configuration\WarehouseFormat;
use Alxarafe\App\Domain\Shared\ValueObject\Uuid;

final readonly class ConfigureWarehouse
{
    public function __construct(private ConfigurationRepository $repository)
    {
    }

    public function createWarehouse(WarehouseConfiguration $warehouse): WarehouseConfiguration
    {
        $this->repository->addWarehouse($warehouse);
        return $warehouse;
    }

    public function createHandlingUnitType(HandlingUnitType $type): HandlingUnitType
    {
        $this->repository->addHandlingUnitType($type);
        return $type;
    }

    public function createLocationType(LocationType $type): LocationType
    {
        $this->requireWarehouse($type->warehouseId);
        $this->repository->addLocationType($type);
        return $type;
    }

    public function createPolicy(string $warehouseId, LocationTypeHuPolicy $policy): LocationTypeHuPolicy
    {
        $this->requireLocationType($warehouseId, $policy->locationTypeId);
        if ($this->repository->handlingUnitType($policy->handlingUnitTypeId) === null) {
            throw new ConfigurationNotFound('Handling unit type not found.');
        }
        $this->repository->addPolicy($policy);
        return $policy;
    }

    /** @return list<WarehouseConfiguration> */
    public function warehouses(): array
    {
        return $this->repository->warehouses();
    }

    /** @return list<HandlingUnitType> */
    public function handlingUnitTypes(): array
    {
        return $this->repository->handlingUnitTypes();
    }

    /** @return list<LocationType> */
    public function locationTypes(string $warehouseId): array
    {
        $this->requireWarehouse($warehouseId);
        return $this->repository->locationTypes($warehouseId);
    }

    /** @return list<LocationTypeHuPolicy> */
    public function policies(string $warehouseId, string $locationTypeId): array
    {
        $this->requireLocationType($warehouseId, $locationTypeId);
        return $this->repository->policies($locationTypeId);
    }

    public function changeFormat(string $warehouseId, WarehouseFormat $format): WarehouseConfiguration
    {
        Uuid::validate($warehouseId);
        return $this->repository->withWarehouseLock($warehouseId, function () use ($warehouseId, $format): WarehouseConfiguration {
            $warehouse = $this->requireWarehouse($warehouseId);
            $warehouse->format->assertChangeAllowed($format, $this->repository->hasAisles($warehouseId));
            $this->repository->saveFormat($warehouseId, $format);
            return new WarehouseConfiguration($warehouse->id, $warehouse->code, $warehouse->name, $format);
        });
    }

    private function requireWarehouse(string $id): WarehouseConfiguration
    {
        Uuid::validate($id);
        return $this->repository->warehouse($id) ?? throw new ConfigurationNotFound('Warehouse not found.');
    }

    private function requireLocationType(string $warehouseId, string $id): LocationType
    {
        $this->requireWarehouse($warehouseId);
        Uuid::validate($id);
        $type = $this->repository->locationType($id)
            ?? throw new ConfigurationNotFound('Location type not found.');
        if (strtolower($type->warehouseId) !== strtolower($warehouseId)) {
            throw new ConfigurationConflict('Location type belongs to another warehouse.');
        }
        return $type;
    }
}
