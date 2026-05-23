<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Inventory\Entity;

use Alxarafe\App\Domain\Catalogue\ValueObject\ItemId;
use Alxarafe\App\Domain\Inventory\ValueObject\BatchId;
use Alxarafe\App\Domain\Inventory\ValueObject\HandlingUnitId;
use Alxarafe\App\Domain\Inventory\ValueObject\HuStatus;
use Alxarafe\App\Domain\Inventory\ValueObject\Quantity;
use Alxarafe\App\Domain\Inventory\ValueObject\Sscc;
use Alxarafe\App\Domain\Inventory\ValueObject\StockQuantId;
use Alxarafe\App\Domain\Topology\ValueObject\LocationId;
use InvalidArgumentException;

/**
 * Aggregate Root representing a Handling Unit (e.g., pallet, box, container).
 *
 * Can contain StockQuants or nest other Handling Units (parent-child relationship).
 */
final class HandlingUnit
{
    /** @var StockQuant[] */
    private array $quants = [];

    public function __construct(
        private readonly HandlingUnitId $id,
        private readonly Sscc $code,
        private ?LocationId $locationId,
        private ?HandlingUnitId $parentHuId,
        private HuStatus $status,
    ) {
        if ($parentHuId !== null && $id->equals($parentHuId)) {
            throw new InvalidArgumentException(
                'HandlingUnit cannot be its own parent.'
            );
        }
    }

    public function id(): HandlingUnitId
    {
        return $this->id;
    }

    public function code(): Sscc
    {
        return $this->code;
    }

    public function locationId(): ?LocationId
    {
        return $this->locationId;
    }

    public function parentHuId(): ?HandlingUnitId
    {
        return $this->parentHuId;
    }

    public function status(): HuStatus
    {
        return $this->status;
    }

    /**
     * @return StockQuant[]
     */
    public function quants(): array
    {
        return $this->quants;
    }

    public function isAvailable(): bool
    {
        return $this->status === HuStatus::AVAILABLE;
    }

    public function moveToLocation(LocationId $locationId): void
    {
        $this->locationId = $locationId;
        // When placed inside a physical location directly, it decoupled from any parent HU
        $this->parentHuId = null;
    }

    public function nestIntoParent(HandlingUnitId $parentHuId): void
    {
        if ($this->id->equals($parentHuId)) {
            throw new InvalidArgumentException(
                'HandlingUnit cannot be nested into itself.'
            );
        }
        $this->parentHuId = $parentHuId;
        // When nested, it inherits/resides in the same location as parent,
        // so its direct physical location reference is cleared.
        $this->locationId = null;
    }

    public function releaseFromParent(?LocationId $newLocationId = null): void
    {
        $this->parentHuId = null;
        $this->locationId = $newLocationId;
    }

    public function updateStatus(HuStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * Adds an item to this Handling Unit.
     */
    public function addQuant(StockQuantId $quantId, ItemId $itemId, ?BatchId $batchId, Quantity $quantity): void
    {
        foreach ($this->quants as $quant) {
            if ($quant->itemId()->equals($itemId) && $this->equalsBatchId($quant->batchId(), $batchId)) {
                $quant->add($quantity);
                return;
            }
        }

        $this->quants[] = new StockQuant(
            $quantId,
            $this->id,
            $itemId,
            $batchId,
            $quantity
        );
    }

    private function equalsBatchId(?BatchId $a, ?BatchId $b): bool
    {
        if ($a === null && $b === null) {
            return true;
        }
        if ($a === null || $b === null) {
            return false;
        }
        return $a->equals($b);
    }
}
