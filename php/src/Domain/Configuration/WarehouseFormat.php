<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Configuration;

use InvalidArgumentException;

final readonly class WarehouseFormat
{
    public function __construct(
        public int $aisleDigits,
        public int $bayDigits,
        public int $levelDigits,
        public bool $usesZones = false,
        public string $separator = '.',
        public bool $includeZoneInCode = false,
    ) {
        foreach ([$aisleDigits, $bayDigits, $levelDigits] as $digits) {
            if ($digits < 1 || $digits > 9) {
                throw new InvalidArgumentException('Format digits must be between 1 and 9.');
            }
        }
        ConfigurationText::validate($separator, 1, 'separator');
        if ($includeZoneInCode && !$usesZones) {
            throw new InvalidArgumentException('Including zone in code requires uses_zones.');
        }
    }

    public function assertChangeAllowed(self $next, bool $hasAisles): void
    {
        if ($hasAisles && $this != $next) {
            throw new WarehouseFormatLocked('Warehouse format is locked after the first aisle.');
        }
    }
}
