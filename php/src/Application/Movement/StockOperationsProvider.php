<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Movement;

use Alxarafe\App\Domain\Inventory\ValueObject\Quantity;

/**
 * Puerto de salida de las operaciones de stock (entrada y salida)
 * para POST /api/receipts y POST /api/issues.
 *
 * Aplica el modelo discreto: 1 HU por hueco, HU monoreferencia y
 * desocupación completa en la salida. Devuelve la proyección del hueco
 * afectado (LocationView, mismo contrato que el visor).
 *
 * @phpstan-import-type LocationView from \Alxarafe\App\Application\State\WarehouseStateProvider
 */
interface StockOperationsProvider
{
    /**
     * @param string|null $expirationDate caducidad del lote en la recepción
     *                                    (ISO-8601, opcional)
     *
     * @return LocationView
     */
    public function receive(
        string $locationId,
        string $itemCode,
        Quantity $quantity,
        ?string $batchCode,
        ?string $expirationDate = null,
    ): array;

    /**
     * @return LocationView
     */
    public function issue(string $locationId, string $itemCode, Quantity $quantity): array;
}
