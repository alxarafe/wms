<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Persistence;

use Alxarafe\App\Application\Configuration\ConfigurationConflict;
use Alxarafe\App\Application\Configuration\ConfigurationNotFound;
use Alxarafe\App\Application\Configuration\ConfigurationRepository;
use Alxarafe\App\Domain\Configuration\HandlingUnitType;
use Alxarafe\App\Domain\Configuration\LocationType;
use Alxarafe\App\Domain\Configuration\LocationTypeHuPolicy;
use Alxarafe\App\Domain\Configuration\WarehouseConfiguration;
use Alxarafe\App\Domain\Configuration\WarehouseFormat;
use Alxarafe\App\Domain\Configuration\WarehouseFormatLocked;
use Alxarafe\App\Infrastructure\Config\Database;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

final readonly class PdoConfigurationRepository implements ConfigurationRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function addWarehouse(WarehouseConfiguration $warehouse): void
    {
        $this->insert('warehouse', ConfigurationRecord::encode($warehouse));
    }

    public function warehouse(string $id): ?WarehouseConfiguration
    {
        $row = $this->one('warehouse', $id);
        return $row === null ? null : ConfigurationRecord::warehouse($row);
    }

    public function warehouses(): array
    {
        return array_map(ConfigurationRecord::warehouse(...), $this->rows('warehouse'));
    }

    public function addHandlingUnitType(HandlingUnitType $type): void
    {
        $this->insert('handling_unit_type', ConfigurationRecord::encode($type));
    }

    public function handlingUnitType(string $id): ?HandlingUnitType
    {
        $row = $this->one('handling_unit_type', $id);
        return $row === null ? null : ConfigurationRecord::handlingUnitType($row);
    }

    public function handlingUnitTypes(): array
    {
        return array_map(ConfigurationRecord::handlingUnitType(...), $this->rows('handling_unit_type'));
    }

    public function addLocationType(LocationType $type): void
    {
        $this->insert('location_type', ConfigurationRecord::encode($type));
    }

    public function locationType(string $id): ?LocationType
    {
        $row = $this->one('location_type', $id);
        return $row === null ? null : ConfigurationRecord::locationType($row);
    }

    public function locationTypes(string $warehouseId): array
    {
        return array_map(ConfigurationRecord::locationType(...), $this->rows('location_type', [
            'warehouse_id' => $warehouseId,
        ]));
    }

    public function addPolicy(LocationTypeHuPolicy $policy): void
    {
        $this->insert('location_type_hu_policy', ConfigurationRecord::encode($policy));
    }

    public function policies(string $locationTypeId): array
    {
        return array_map(ConfigurationRecord::policy(...), $this->rows('location_type_hu_policy', [
            'location_type_id' => $locationTypeId,
        ], 'handling_unit_type_id'));
    }

    public function withWarehouseLock(string $id, callable $operation): WarehouseConfiguration
    {
        $this->pdo->beginTransaction();
        try {
            $this->statement('SELECT id FROM ' . Database::qualified('warehouse') . ' WHERE id = :id FOR UPDATE', [
                'id' => $id,
            ]);
            $result = $operation();
            $this->pdo->commit();
            return $result;
        } catch (Throwable $error) {
            $this->pdo->rollBack();
            throw $error;
        }
    }

    public function hasAisles(string $warehouseId): bool
    {
        return $this->statement('SELECT 1 FROM ' . Database::qualified('aisle') . ' WHERE warehouse_id = :id LIMIT 1', [
            'id' => $warehouseId,
        ])->fetchColumn() !== false;
    }

    public function saveFormat(string $warehouseId, WarehouseFormat $format): void
    {
        $values = ConfigurationRecord::format($format);
        $assignments = array_map(static fn (string $key): string => "$key = :$key", array_keys($values));
        $this->statement('UPDATE ' . Database::qualified('warehouse') . ' SET ' . implode(', ', $assignments)
            . ' WHERE id = :id', [...$values, 'id' => $warehouseId]);
    }

    /** @param array<string, scalar|null> $values */
    private function insert(string $table, array $values): void
    {
        $columns = array_keys($values);
        $this->statement('INSERT INTO ' . Database::qualified($table) . ' (' . implode(', ', $columns) . ') VALUES (:'
            . implode(', :', $columns) . ')', $values);
    }

    /** @return array<string, scalar|null>|null */
    private function one(string $table, string $id): ?array
    {
        $row = $this->statement('SELECT * FROM ' . Database::qualified($table) . ' WHERE id = :id', ['id' => $id])
            ->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * @param array<string, string> $filters
     * @return list<array<string, scalar|null>>
     */
    private function rows(string $table, array $filters = [], string $order = 'code'): array
    {
        $conditions = array_map(static fn (string $key): string => "$key = :$key", array_keys($filters));
        return array_values($this->statement('SELECT * FROM ' . Database::qualified($table)
            . ($conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions))
            . ' ORDER BY ' . $order, $filters)->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @param array<string, scalar|null> $values */
    private function statement(string $sql, array $values = []): PDOStatement
    {
        try {
            $statement = $this->pdo->prepare($sql);
            foreach ($values as $key => $value) {
                $type = match (true) {
                    $value === null => PDO::PARAM_NULL,
                    is_bool($value) => PDO::PARAM_BOOL,
                    is_int($value) => PDO::PARAM_INT,
                    default => PDO::PARAM_STR,
                };
                $statement->bindValue(':' . $key, $value, $type);
            }
            $statement->execute();
            return $statement;
        } catch (PDOException $error) {
            throw match ($error->getCode()) {
                'WMS01' => new WarehouseFormatLocked('Warehouse format is locked after the first aisle.', 0, $error),
                '23505' => new ConfigurationConflict('Configuration already exists.', 0, $error),
                '23503' => new ConfigurationNotFound('Referenced configuration not found.', 0, $error),
                default => $error,
            };
        }
    }
}
