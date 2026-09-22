<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Configuration;

use Alxarafe\App\Domain\Shared\ValueObject\Uuid;
use InvalidArgumentException;

final readonly class LocationTypeHuPolicy
{
    public function __construct(
        public string $locationTypeId,
        public string $handlingUnitTypeId,
        public bool $acceptsFull = false,
        public bool $acceptsPartial = false,
        public bool $allowsBreakdown = false,
        public bool $allowsFullDispatch = false,
    ) {
        Uuid::validate($locationTypeId);
        Uuid::validate($handlingUnitTypeId);
        if (!$acceptsFull && !$acceptsPartial) {
            throw new InvalidArgumentException('A policy must accept full or partial HUs.');
        }
        if ($allowsFullDispatch && !$acceptsFull) {
            throw new InvalidArgumentException('Full dispatch requires accepts_full.');
        }
    }
}
