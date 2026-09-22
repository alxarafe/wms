<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Persistence;

use Alxarafe\App\Application\Movement\StockOperationException;
use Alxarafe\App\Application\Movement\StockOperationsProvider;
use Alxarafe\App\Domain\Inventory\ValueObject\HandlingUnitId;
use Alxarafe\App\Domain\Inventory\ValueObject\Quantity;
use Alxarafe\App\Domain\Inventory\ValueObject\Sscc;
use Alxarafe\App\Domain\Inventory\ValueObject\StockQuantId;
use Alxarafe\App\Domain\Rules\ValueObject\StockMovementId;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Throwable;

/**
 * Implementación de las operaciones de stock sobre PostgreSQL.
 *
 * - Entrada (INBOUND): crea HU (SSCC generado) con un stock_quant y registra
 *   el movimiento desde NULL hacia el hueco elegido.
 * - Salida  (OUTBOUND): solo admite consumir el stock completo del hueco
 *   (desocupación completa). Borra el stock_quant, desvincula la HU del hueco
 *   (location_id = NULL, queda como registro histórico porque el ledger
 *   inmutable la referencia) y registra el movimiento de vuelta.
 *
 * @phpstan-import-type LocationView from \Alxarafe\App\Application\State\WarehouseStateProvider
 */
final readonly class PdoStockOperationsProvider implements StockOperationsProvider
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return LocationView */
    public function receive(
        string $locationId,
        string $itemCode,
        Quantity $quantity,
        ?string $batchCode,
        ?string $expirationDate = null,
    ): array {
        $location = $this->findLocation($locationId);
        if ($location === null) {
            throw new StockOperationException('Location not found.', 404);
        }
        if (self::toBool($location['aisle_blocked']) || $location['status'] !== 'ACTIVE') {
            throw new StockOperationException('Location is not available.', 409);
        }
        if ($this->handlingUnitAt($locationId) !== null) {
            throw new StockOperationException('Location is already occupied.', 409);
        }

        $item = $this->findItemBySku($itemCode);
        if ($item === null) {
            throw new StockOperationException('Item not found.', 404);
        }
        $batchId = $this->resolveBatch($item, $batchCode, $expirationDate);

        $huId = HandlingUnitId::generate();
        $quantId = StockQuantId::generate();
        $movementId = StockMovementId::generate();
        $sscc = Sscc::generate();

        $this->pdo->beginTransaction();
        try {
            $this->insertHandlingUnit($huId->value(), $sscc->value(), $locationId);
            $this->insertQuant($quantId->value(), $huId->value(), $item['id'], $batchId, $quantity);
            $this->insertMovement($movementId->value(), 'INBOUND', $huId->value(), null, $locationId);
            $this->pdo->commit();
        } catch (Throwable $error) {
            $this->pdo->rollBack();
            throw $error;
        }

        return $this->locationView($locationId);
    }

    /** @return LocationView */
    public function issue(string $locationId, string $itemCode, Quantity $quantity): array
    {
        $location = $this->findLocation($locationId);
        if ($location === null) {
            throw new StockOperationException('Location not found.', 404);
        }
        $hu = $this->handlingUnitAt($locationId);
        if ($hu === null) {
            throw new StockOperationException('Location has no stock to issue.', 409);
        }

        $quant = $this->findQuant($hu['id'], $itemCode);
        if ($quant === null) {
            throw new StockOperationException("Item {$itemCode} is not stored in this location.", 409);
        }
        $stored = Quantity::fromDecimalString($quant['quantity'], (string) $quant['unit']);
        if ($quantity->unit() !== $stored->unit()) {
            throw new StockOperationException(
                "Cannot issue quantity in unit {$quantity->unit()}: stored stock is in unit {$stored->unit()}.",
                400,
            );
        }
        if (!$quantity->equals($stored)) {
            throw new StockOperationException(
                "Issue quantity does not match the stored stock ({$stored->toDecimalString()} {$stored->unit()}).",
                409,
            );
        }

        $movementId = StockMovementId::generate();

        $this->pdo->beginTransaction();
        try {
            $this->deleteQuant($quant['id']);
            $this->detachHandlingUnit($hu['id']);
            $this->insertMovement($movementId->value(), 'OUTBOUND', $hu['id'], $locationId, null);
            $this->pdo->commit();
        } catch (Throwable $error) {
            $this->pdo->rollBack();
            throw $error;
        }

        return $this->locationView($locationId);
    }

    /**
     * Resuelve el lote de la entrada y aplica la captura de caducidad
     * (decisión del responsable): si el lote no tiene caducidad almacenada,
     * se establece con el valor enviado; si ya tiene una distinta, se rechaza.
     *
     * @param array{id: string, is_batch_managed: mixed} $item
     */
    private function resolveBatch(array $item, ?string $batchCode, ?string $expirationDate): ?string
    {
        $batchManaged = self::toBool($item['is_batch_managed']);

        $expiration = null;
        if ($expirationDate !== null && $expirationDate !== '') {
            if ($batchCode === null || $batchCode === '') {
                throw new StockOperationException('Expiration date requires a batch code.', 400);
            }
            $expiration = self::parseExpirationDate($expirationDate);
        }

        if ($batchManaged && ($batchCode === null || $batchCode === '')) {
            throw new StockOperationException("Batch code is required for item {$item['id']}.", 400);
        }
        if (!$batchManaged && $batchCode !== null && $batchCode !== '') {
            throw new StockOperationException("Item {$item['id']} is not batch managed.", 400);
        }
        if (!$batchManaged && $expiration !== null) {
            throw new StockOperationException('Expiration date is not allowed for a non-batch-managed item.', 400);
        }
        if ($batchCode === null || $batchCode === '') {
            return null;
        }

        $statement = $this->pdo->prepare('SELECT id, expiration_date FROM batch WHERE item_id = ? AND batch_code = ?');
        $statement->execute([$item['id'], $batchCode]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            throw new StockOperationException("Batch not found for item {$item['id']}.", 404);
        }
        $batchId = (string) $row['id'];

        if ($expiration !== null) {
            $stored = $row['expiration_date'] === null
                ? null
                : new DateTimeImmutable((string) $row['expiration_date']);
            if ($stored === null) {
                $update = $this->pdo->prepare('UPDATE batch SET expiration_date = ? WHERE id = ?');
                $update->execute([$expiration->format(DATE_ATOM), $batchId]);
            } elseif ($stored->format('U') !== $expiration->format('U')) {
                throw new StockOperationException(
                    "Expiration date does not match the stored expiration of batch {$batchCode}.",
                    409,
                );
            }
        }

        return $batchId;
    }

    /**
     * Acepta una fecha (YYYY-MM-DD, medianoche UTC) o un instante ISO-8601
     * con zona horaria; ambos se normalizan a UTC.
     */
    private static function parseExpirationDate(string $value): DateTimeImmutable
    {
        $candidates = [
            ['Y-m-d\TH:i:sP', null],
            ['Y-m-d', new DateTimeZone('UTC')],
        ];
        foreach ($candidates as [$format, $timezone]) {
            $parsed = DateTimeImmutable::createFromFormat($format, $value, $timezone);
            if (!$parsed instanceof DateTimeImmutable) {
                continue;
            }
            $errors = DateTimeImmutable::getLastErrors();
            if (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                continue;
            }
            return $parsed->setTimezone(new DateTimeZone('UTC'));
        }
        throw new StockOperationException('Expiration date must be an ISO-8601 date or datetime.', 400);
    }

    /** @return array{id: string, status: string, aisle_blocked: bool}|null */
    private function findLocation(string $locationId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT l.id, l.status, a.is_blocked AS aisle_blocked '
            . 'FROM location l JOIN aisle a ON a.id = l.aisle_id WHERE l.id = ?'
        );
        $statement->execute([$locationId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }
        return [
            'id' => (string) $row['id'],
            'status' => (string) $row['status'],
            'aisle_blocked' => (bool) $row['aisle_blocked'],
        ];
    }

    /** @return array{id: string}|null */
    private function handlingUnitAt(string $locationId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id FROM handling_unit WHERE location_id = ? AND parent_hu_id IS NULL'
        );
        $statement->execute([$locationId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }
        return ['id' => (string) $row['id']];
    }

    /** @return array{id: string, is_batch_managed: bool}|null */
    private function findItemBySku(string $sku): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, is_batch_managed FROM item WHERE sku = ?');
        $statement->execute([$sku]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }
        return [
            'id' => (string) $row['id'],
            'is_batch_managed' => (bool) $row['is_batch_managed'],
        ];
    }

    /**
     * @return array{id: string, quantity: string, unit: string}|null
     */
    private function findQuant(string $huId, string $itemCode): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT sq.id, sq.quantity, sq.unit FROM stock_quant sq '
            . 'JOIN item i ON i.id = sq.item_id WHERE sq.hu_id = ? AND i.sku = ?'
        );
        $statement->execute([$huId, $itemCode]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }
        return [
            'id' => (string) $row['id'],
            'quantity' => (string) $row['quantity'],
            'unit' => (string) $row['unit'],
        ];
    }

    private function insertHandlingUnit(string $huId, string $sscc, string $locationId): void
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO handling_unit (id, code, location_id, parent_hu_id, status) "
            . "VALUES (?, ?, ?, NULL, 'AVAILABLE')"
        );
        $statement->execute([$huId, $sscc, $locationId]);
    }

    private function insertQuant(
        string $quantId,
        string $huId,
        string $itemId,
        ?string $batchId,
        Quantity $quantity,
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO stock_quant (id, hu_id, item_id, batch_id, quantity, unit) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $quantId,
            $huId,
            $itemId,
            $batchId,
            $quantity->toDecimalString(),
            $quantity->unit(),
        ]);
    }

    private function insertMovement(
        string $movementId,
        string $type,
        string $huId,
        ?string $from,
        ?string $to,
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO stock_movement (id, type, hu_id, from_location_id, to_location_id) '
            . 'VALUES (?, ?, ?, ?, ?)'
        );
        $statement->execute([$movementId, $type, $huId, $from, $to]);
    }

    private function deleteQuant(string $quantId): void
    {
        $this->pdo->prepare('DELETE FROM stock_quant WHERE id = ?')->execute([$quantId]);
    }

    private function detachHandlingUnit(string $huId): void
    {
        $this->pdo
            ->prepare('UPDATE handling_unit SET location_id = NULL WHERE id = ?')
            ->execute([$huId]);
    }

    /** @return LocationView */
    private function locationView(string $locationId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                l.id AS location_id, l.code AS location_code, l.bay, l.level, l.role, l.status,
                a.is_blocked AS aisle_blocked,
                hu.code AS hu_code,
                i.id AS item_id, i.sku AS item_code, i.name AS name,
                sq.quantity, sq.unit,
                b.batch_code
            FROM location l
            JOIN aisle a ON a.id = l.aisle_id
            LEFT JOIN handling_unit hu ON hu.location_id = l.id AND hu.parent_hu_id IS NULL
            LEFT JOIN stock_quant sq ON sq.hu_id = hu.id
            LEFT JOIN item i ON i.id = sq.item_id
            LEFT JOIN batch b ON b.id = sq.batch_id
            WHERE l.id = ?'
        );
        $statement->execute([$locationId]);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $references = [];
        foreach ($rows as $row) {
            if ($row['hu_code'] === null || $row['item_code'] === null) {
                continue;
            }
            $references[] = [
                'itemId' => (string) $row['item_id'],
                'itemCode' => (string) $row['item_code'],
                'name' => (string) $row['name'],
                'quantity' => (float) $row['quantity'],
                'unit' => (string) $row['unit'],
                'batchCode' => self::optionalString($row['batch_code']),
                'huCode' => (string) $row['hu_code'],
            ];
        }

        $first = $rows[0];

        return [
            'id' => (string) $first['location_id'],
            'code' => (string) $first['location_code'],
            'bay' => (int) $first['bay'],
            'level' => (int) $first['level'],
            'role' => (string) $first['role'],
            'status' => (string) $first['status'],
            'blocked' => self::toBool($first['aisle_blocked']) || $first['status'] !== 'ACTIVE',
            'references' => $references,
        ];
    }

    private static function optionalString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    private static function toBool(mixed $value): bool
    {
        return $value === true || $value === 't' || $value === '1' || $value === 1;
    }
}
