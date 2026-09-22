<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Configuration;

use Alxarafe\App\Domain\Shared\ValueObject\Uuid;
use InvalidArgumentException;

final readonly class LocationType
{
    public function __construct(
        public string $id,
        public string $warehouseId,
        public string $code,
        public string $name,
        public ?int $maxLocationsPerItem = null,
        public bool $allowsMultiSku = false,
        public bool $allowsMultiBatch = false,
    ) {
        Uuid::validate($id);
        Uuid::validate($warehouseId);
        ConfigurationText::validate($code, 30, 'code');
        ConfigurationText::validate($name, 255, 'name');
        if ($maxLocationsPerItem !== null && ($maxLocationsPerItem < 1 || $maxLocationsPerItem > 2147483647)) {
            throw new InvalidArgumentException('max_locations_per_item must be null or a positive 32-bit integer.');
        }
    }
}
