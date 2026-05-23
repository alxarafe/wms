<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Topology\Entity;

use Alxarafe\App\Domain\Topology\ValueObject\AisleCode;
use Alxarafe\App\Domain\Topology\ValueObject\AisleId;
use Alxarafe\App\Domain\Topology\ValueObject\ZoneId;

/**
 * Entity representing a physical aisle within a zone.
 *
 * Aisles serve as the container for mass-blocking operations.
 * Blocking an aisle effectively blocks all locations within it.
 */
final class Aisle
{
    private bool $blocked = false;

    public function __construct(
        private readonly AisleId $id,
        private readonly ZoneId $zoneId,
        private readonly AisleCode $code,
    ) {
    }

    public function id(): AisleId
    {
        return $this->id;
    }

    public function zoneId(): ZoneId
    {
        return $this->zoneId;
    }

    public function code(): AisleCode
    {
        return $this->code;
    }

    public function isBlocked(): bool
    {
        return $this->blocked;
    }

    public function block(): void
    {
        $this->blocked = true;
    }

    public function unblock(): void
    {
        $this->blocked = false;
    }
}
