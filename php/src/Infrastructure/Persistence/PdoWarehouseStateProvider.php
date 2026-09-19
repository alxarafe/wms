<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Persistence;

use Alxarafe\App\Application\State\WarehouseStateProvider;
use PDO;

/**
 * @phpstan-import-type WarehouseStateView from WarehouseStateProvider
 */
final readonly class PdoWarehouseStateProvider implements WarehouseStateProvider
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return WarehouseStateView|null */
    public function stateFor(string $warehouseId): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, code, name FROM warehouse WHERE id = ?');
        $statement->execute([$warehouseId]);
        $warehouse = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($warehouse)) {
            return null;
        }

        $locations = $this->fetchLocations($warehouseId);
        $zones = $this->groupZones($locations);

        return [
            'id' => $warehouse['id'],
            'code' => $warehouse['code'],
            'name' => $warehouse['name'],
            'zones' => $zones,
        ];
    }

    /**
     * @return list<array{
     *   zoneId: string,
     *   zoneCode: string,
     *   zoneTypeCode: string,
     *   allowsMultiSku: bool,
     *   isOperative: bool,
     *   aisleId: string,
     *   aisleCode: string,
     *   aisleBlocked: bool,
     *   locationId: string,
     *   locationCode: string,
     *   bay: int,
     *   level: int,
     *   role: string,
     *   status: string,
     *   huCode: string|null,
     *   itemId: string|null,
     *   itemCode: string|null,
     *   name: string|null,
     *   quantity: float|null,
     *   unit: string|null,
     *   batchCode: string|null,
     * }>
     */
    private function fetchLocations(string $warehouseId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                l.id AS location_id, l.code AS location_code, l.bay, l.level, l.role, l.status,
                a.id AS aisle_id, a.code AS aisle_code, a.is_blocked AS aisle_blocked,
                z.id AS zone_id, z.code AS zone_code,
                zt.code AS zone_type_code, zt.allows_multi_sku, zt.is_operative,
                hu.code AS hu_code,
                i.id AS item_id, i.sku AS item_code, i.name AS name,
                sq.quantity, sq.unit,
                b.batch_code
            FROM location l
            JOIN aisle a ON a.id = l.aisle_id
            JOIN zone z ON z.id = a.zone_id
            JOIN zone_type zt ON zt.id = z.zone_type_id
            LEFT JOIN handling_unit hu ON hu.location_id = l.id AND hu.parent_hu_id IS NULL
            LEFT JOIN stock_quant sq ON sq.hu_id = hu.id
            LEFT JOIN item i ON i.id = sq.item_id
            LEFT JOIN batch b ON b.id = sq.batch_id
            WHERE z.warehouse_id = ?
            ORDER BY z.code, a.code, l.level DESC, l.bay'
        );
        $statement->execute([$warehouseId]);

        return array_values(array_map(static fn (array $row): array => [
            'zoneId' => (string) $row['zone_id'],
            'zoneCode' => (string) $row['zone_code'],
            'zoneTypeCode' => (string) $row['zone_type_code'],
            'allowsMultiSku' => self::toBool($row['allows_multi_sku']),
            'isOperative' => self::toBool($row['is_operative']),
            'aisleId' => (string) $row['aisle_id'],
            'aisleCode' => (string) $row['aisle_code'],
            'aisleBlocked' => self::toBool($row['aisle_blocked']),
            'locationId' => (string) $row['location_id'],
            'locationCode' => (string) $row['location_code'],
            'bay' => (int) $row['bay'],
            'level' => (int) $row['level'],
            'role' => (string) $row['role'],
            'status' => (string) $row['status'],
            'huCode' => self::optionalString($row['hu_code']),
            'itemId' => self::optionalString($row['item_id']),
            'itemCode' => self::optionalString($row['item_code']),
            'name' => self::optionalString($row['name']),
            'quantity' => $row['quantity'] === null ? null : (float) $row['quantity'],
            'unit' => self::optionalString($row['unit']),
            'batchCode' => self::optionalString($row['batch_code']),
        ], $statement->fetchAll(PDO::FETCH_ASSOC)));
    }

    private static function optionalString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    /**
     * @param list<array{
     *   zoneId: string,
     *   zoneCode: string,
     *   zoneTypeCode: string,
     *   allowsMultiSku: bool,
     *   isOperative: bool,
     *   aisleId: string,
     *   aisleCode: string,
     *   aisleBlocked: bool,
     *   locationId: string,
     *   locationCode: string,
     *   bay: int,
     *   level: int,
     *   role: string,
     *   status: string,
     *   huCode: string|null,
     *   itemId: string|null,
     *   itemCode: string|null,
     *   name: string|null,
     *   quantity: float|null,
     *   unit: string|null,
     *   batchCode: string|null,
     * }> $rows
     * @return list<array{
     *   id: string,
     *   code: string,
     *   zoneTypeCode: string,
     *   allowsMultiSku: bool,
     *   isOperative: bool,
     *   aisles: list<array{
     *     id: string,
     *     code: string,
     *     zoneId: string,
     *     bays: int,
     *     levels: int,
     *     isBlocked: bool,
     *     locations: list<array{
     *       id: string,
     *       code: string,
     *       bay: int,
     *       level: int,
     *       role: string,
     *       status: string,
     *       blocked: bool,
     *       references: list<array{
     *         itemId: string,
     *         itemCode: string,
     *         name: string,
     *         quantity: float,
     *         unit: string,
     *         batchCode: string|null,
     *         huCode: string|null,
     *       }>,
     *     }>,
     *   }>,
     * }>
     */
    private function groupZones(array $rows): array
    {
        $zones = [];
        $index = [];
        foreach ($rows as $row) {
            $zoneKey = $row['zoneId'];
            if (!isset($index[$zoneKey])) {
                $zones[] = [
                    'id' => $zoneKey,
                    'code' => $row['zoneCode'],
                    'zoneTypeCode' => $row['zoneTypeCode'],
                    'allowsMultiSku' => $row['allowsMultiSku'],
                    'isOperative' => $row['isOperative'],
                    'aisles' => [],
                ];
                $index[$zoneKey] = count($zones) - 1;
            }
            $zoneRef = &$zones[$index[$zoneKey]];

            $aisleKey = $zoneKey . '|' . $row['aisleId'];
            if (!isset($index[$aisleKey])) {
                $zoneRef['aisles'][] = [
                    'id' => $row['aisleId'],
                    'code' => $row['aisleCode'],
                    'zoneId' => $zoneKey,
                    'bays' => 0,
                    'levels' => 0,
                    'isBlocked' => $row['aisleBlocked'],
                    'locations' => [],
                ];
                $index[$aisleKey] = count($zoneRef['aisles']) - 1;
            }
            $aisleRef = &$zoneRef['aisles'][$index[$aisleKey]];
            $aisleRef['bays'] = max($aisleRef['bays'], $row['bay']);
            $aisleRef['levels'] = max($aisleRef['levels'], $row['level']);

            $locationKey = $aisleKey . '|' . $row['locationId'];
            if (!isset($index[$locationKey])) {
                $aisleRef['locations'][] = [
                    'id' => $row['locationId'],
                    'code' => $row['locationCode'],
                    'bay' => $row['bay'],
                    'level' => $row['level'],
                    'role' => $row['role'],
                    'status' => $row['status'],
                    'blocked' => $row['aisleBlocked'] || $row['status'] !== 'ACTIVE',
                    'references' => [],
                ];
                $index[$locationKey] = count($aisleRef['locations']) - 1;
            }
            $locationRef = &$aisleRef['locations'][$index[$locationKey]];

            if ($row['huCode'] !== null && $row['itemCode'] !== null) {
                $locationRef['references'][] = [
                    'itemId' => (string) $row['itemId'],
                    'itemCode' => $row['itemCode'],
                    'name' => (string) $row['name'],
                    'quantity' => (float) $row['quantity'],
                    'unit' => (string) $row['unit'],
                    'batchCode' => $row['batchCode'],
                    'huCode' => $row['huCode'],
                ];
            }
            unset($zoneRef, $aisleRef, $locationRef);
        }

        return $zones;
    }

    private static function toBool(mixed $value): bool
    {
        return $value === true || $value === 't' || $value === '1' || $value === 1;
    }
}
