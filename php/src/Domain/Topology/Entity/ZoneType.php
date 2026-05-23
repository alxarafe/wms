<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Topology\Entity;

use Alxarafe\App\Domain\Topology\ValueObject\ZoneTypeCode;
use Alxarafe\App\Domain\Topology\ValueObject\ZoneTypeId;

/**
 * Master entity representing the type of a warehouse zone.
 *
 * Defines operational characteristics like whether the zone
 * is operative and whether it allows multi-SKU storage.
 */
final readonly class ZoneType
{
    public function __construct(
        private ZoneTypeId $id,
        private ZoneTypeCode $code,
        private bool $isOperative,
        private bool $allowsMultiSku,
    ) {
    }

    public function id(): ZoneTypeId
    {
        return $this->id;
    }

    public function code(): ZoneTypeCode
    {
        return $this->code;
    }

    public function isOperative(): bool
    {
        return $this->isOperative;
    }

    public function allowsMultiSku(): bool
    {
        return $this->allowsMultiSku;
    }
}
