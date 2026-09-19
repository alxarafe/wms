<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\State;

/**
 * Puerto de salida que entrega la proyección del estado de un almacén
 * para el visor (GET /api/warehouses/{id}/state).
 *
 * Devolvemos un array con el contrato del visor (camelCase), sin acoplar
 * el cliente ni la API a una entidad de dominio concreta.
 *
 * @phpstan-type ReferenceStockView array{
 *   itemId: string,
 *   itemCode: string,
 *   name: string,
 *   quantity: float,
 *   unit: string,
 *   batchCode: string|null,
 *   huCode: string|null,
 * }
 * @phpstan-type LocationView array{
 *   id: string,
 *   code: string,
 *   bay: int,
 *   level: int,
 *   role: string,
 *   status: string,
 *   blocked: bool,
 *   references: list<ReferenceStockView>,
 * }
 * @phpstan-type AisleView array{
 *   id: string,
 *   code: string,
 *   zoneId: string,
 *   bays: int,
 *   levels: int,
 *   isBlocked: bool,
 *   locations: list<LocationView>,
 * }
 * @phpstan-type ZoneView array{
 *   id: string,
 *   code: string,
 *   zoneTypeCode: string,
 *   allowsMultiSku: bool,
 *   isOperative: bool,
 *   aisles: list<AisleView>,
 * }
 * @phpstan-type WarehouseStateView array{
 *   id: string,
 *   code: string,
 *   name: string,
 *   zones: list<ZoneView>,
 * }
 */
interface WarehouseStateProvider
{
    /** @return WarehouseStateView|null */
    public function stateFor(string $warehouseId): ?array;
}
