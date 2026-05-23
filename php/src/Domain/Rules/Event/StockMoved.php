<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Rules\Event;

use Alxarafe\App\Domain\Inventory\ValueObject\HandlingUnitId;
use Alxarafe\App\Domain\Rules\ValueObject\MovementType;
use Alxarafe\App\Domain\Rules\ValueObject\StockMovementId;
use Alxarafe\App\Domain\Topology\ValueObject\LocationId;
use DateTimeImmutable;

/**
 * Domain Event and ledger representing a recorded movement of a Handling Unit.
 */
final readonly class StockMoved
{
    public function __construct(
        private StockMovementId $id,
        private MovementType $type,
        private HandlingUnitId $huId,
        private ?LocationId $fromLocationId,
        private LocationId $toLocationId,
        private DateTimeImmutable $performedAt,
    ) {
    }

    public function id(): StockMovementId
    {
        return $this->id;
    }

    public function type(): MovementType
    {
        return $this->type;
    }

    public function huId(): HandlingUnitId
    {
        return $this->huId;
    }

    public function fromLocationId(): ?LocationId
    {
        return $this->fromLocationId;
    }

    public function toLocationId(): LocationId
    {
        return $this->toLocationId;
    }

    public function performedAt(): DateTimeImmutable
    {
        return $this->performedAt;
    }
}
