<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Topology\Entity;

use Alxarafe\App\Domain\Topology\ValueObject\AisleId;
use Alxarafe\App\Domain\Topology\ValueObject\LocationCode;
use Alxarafe\App\Domain\Topology\ValueObject\LocationId;
use Alxarafe\App\Domain\Topology\ValueObject\LocationRole;
use Alxarafe\App\Domain\Topology\ValueObject\LocationStatus;
use InvalidArgumentException;

/**
 * Entity representing a specific storage location within an aisle.
 *
 * Identified by aisle + bay + level. The composite code is generated
 * by the LocationCodeGenerator domain service and persisted.
 */
final class Location
{
    public function __construct(
        private readonly LocationId $id,
        private readonly AisleId $aisleId,
        private readonly int $bay,
        private readonly int $level,
        private LocationCode $code,
        private readonly LocationRole $role,
        private LocationStatus $status,
    ) {
        if ($bay < 1) {
            throw new InvalidArgumentException(
                'Location bay must be a positive integer. Got: ' . $bay
            );
        }

        if ($level < 1) {
            throw new InvalidArgumentException(
                'Location level must be a positive integer. Got: ' . $level
            );
        }
    }

    public function id(): LocationId
    {
        return $this->id;
    }

    public function aisleId(): AisleId
    {
        return $this->aisleId;
    }

    public function bay(): int
    {
        return $this->bay;
    }

    public function level(): int
    {
        return $this->level;
    }

    public function code(): LocationCode
    {
        return $this->code;
    }

    public function role(): LocationRole
    {
        return $this->role;
    }

    public function status(): LocationStatus
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === LocationStatus::ACTIVE;
    }

    public function block(): void
    {
        $this->status = LocationStatus::BLOCKED;
    }

    public function activate(): void
    {
        $this->status = LocationStatus::ACTIVE;
    }

    public function disable(): void
    {
        $this->status = LocationStatus::DISABLED;
    }

    /**
     * Re-generates the location code using a new NamingPolicy.
     * Called when the zone's naming policy changes.
     */
    public function updateCode(LocationCode $newCode): void
    {
        $this->code = $newCode;
    }
}
