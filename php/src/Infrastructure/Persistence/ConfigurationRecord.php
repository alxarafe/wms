<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Persistence;

use Alxarafe\App\Domain\Configuration\HandlingUnitType;
use Alxarafe\App\Domain\Configuration\LocationType;
use Alxarafe\App\Domain\Configuration\LocationTypeHuPolicy;
use Alxarafe\App\Domain\Configuration\WarehouseConfiguration;
use Alxarafe\App\Domain\Configuration\WarehouseFormat;

final class ConfigurationRecord
{
    /** @return array<string, scalar|null> */
    public static function format(WarehouseFormat $format): array
    {
        return [
            'aisle_digits' => $format->aisleDigits,
            'bay_digits' => $format->bayDigits,
            'level_digits' => $format->levelDigits,
            'uses_zones' => $format->usesZones,
            'separator' => $format->separator,
            'include_zone_in_code' => $format->includeZoneInCode,
        ];
    }

    /** @return array<string, scalar|null> */
    public static function encode(
        WarehouseConfiguration|HandlingUnitType|LocationType|LocationTypeHuPolicy $entity,
    ): array {
        return match (true) {
            $entity instanceof WarehouseConfiguration => [
                'id' => $entity->id, 'code' => $entity->code, 'name' => $entity->name,
                ...self::format($entity->format),
            ],
            $entity instanceof HandlingUnitType => [
                'id' => $entity->id, 'code' => $entity->code, 'name' => $entity->name,
                'is_active' => $entity->isActive,
            ],
            $entity instanceof LocationType => [
                'id' => $entity->id, 'warehouse_id' => $entity->warehouseId,
                'code' => $entity->code, 'name' => $entity->name,
                'max_locations_per_item' => $entity->maxLocationsPerItem,
                'allows_multi_sku' => $entity->allowsMultiSku, 'allows_multi_batch' => $entity->allowsMultiBatch,
            ],
            $entity instanceof LocationTypeHuPolicy => [
                'location_type_id' => $entity->locationTypeId, 'handling_unit_type_id' => $entity->handlingUnitTypeId,
                'accepts_full' => $entity->acceptsFull, 'accepts_partial' => $entity->acceptsPartial,
                'allows_breakdown' => $entity->allowsBreakdown, 'allows_full_dispatch' => $entity->allowsFullDispatch,
            ],
        };
    }

    /** @param array<string, scalar|null> $row */
    public static function warehouse(array $row): WarehouseConfiguration
    {
        return new WarehouseConfiguration(
            (string) $row['id'],
            (string) $row['code'],
            (string) $row['name'],
            new WarehouseFormat(
                (int) $row['aisle_digits'],
                (int) $row['bay_digits'],
                (int) $row['level_digits'],
                (bool) $row['uses_zones'],
                (string) $row['separator'],
                (bool) $row['include_zone_in_code'],
            ),
        );
    }

    /** @param array<string, scalar|null> $row */
    public static function handlingUnitType(array $row): HandlingUnitType
    {
        return new HandlingUnitType(
            (string) $row['id'],
            (string) $row['code'],
            (string) $row['name'],
            (bool) $row['is_active'],
        );
    }

    /** @param array<string, scalar|null> $row */
    public static function locationType(array $row): LocationType
    {
        return new LocationType(
            (string) $row['id'],
            (string) $row['warehouse_id'],
            (string) $row['code'],
            (string) $row['name'],
            $row['max_locations_per_item'] === null ? null : (int) $row['max_locations_per_item'],
            (bool) $row['allows_multi_sku'],
            (bool) $row['allows_multi_batch'],
        );
    }

    /** @param array<string, scalar|null> $row */
    public static function policy(array $row): LocationTypeHuPolicy
    {
        return new LocationTypeHuPolicy(
            (string) $row['location_type_id'],
            (string) $row['handling_unit_type_id'],
            (bool) $row['accepts_full'],
            (bool) $row['accepts_partial'],
            (bool) $row['allows_breakdown'],
            (bool) $row['allows_full_dispatch'],
        );
    }
}
