<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Inventory\Entity;

use Alxarafe\App\Domain\Catalogue\ValueObject\ItemId;
use Alxarafe\App\Domain\Inventory\ValueObject\BatchId;
use Alxarafe\App\Domain\Inventory\ValueObject\HandlingUnitId;
use Alxarafe\App\Domain\Inventory\ValueObject\Quantity;
use Alxarafe\App\Domain\Inventory\ValueObject\StockQuantId;
use InvalidArgumentException;

/**
 * Entity representing an indivisible quantity of stock of a particular item/batch
 * within a handling unit.
 */
final class StockQuant
{
    public function __construct(
        private readonly StockQuantId $id,
        private readonly HandlingUnitId $huId,
        private readonly ItemId $itemId,
        private readonly ?BatchId $batchId,
        private Quantity $quantity,
    ) {
    }

    public function id(): StockQuantId
    {
        return $this->id;
    }

    public function huId(): HandlingUnitId
    {
        return $this->huId;
    }

    public function itemId(): ItemId
    {
        return $this->itemId;
    }

    public function batchId(): ?BatchId
    {
        return $this->batchId;
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    public function add(Quantity $qty): void
    {
        $this->quantity = $this->quantity->add($qty);
    }

    public function subtract(Quantity $qty): void
    {
        $this->quantity = $this->quantity->subtract($qty);
    }
}
