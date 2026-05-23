<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Topology\Entity;

use Alxarafe\App\Domain\Topology\ValueObject\NamingPolicy;
use Alxarafe\App\Domain\Topology\ValueObject\WarehouseId;
use Alxarafe\App\Domain\Topology\ValueObject\ZoneCode;
use Alxarafe\App\Domain\Topology\ValueObject\ZoneId;
use Alxarafe\App\Domain\Topology\ValueObject\ZoneTypeId;

/**
 * Entity representing a zone within a warehouse.
 *
 * A zone groups aisles and carries the NamingPolicy used
 * to generate composite location codes.
 */
final readonly class Zone
{
    public function __construct(
        private ZoneId $id,
        private WarehouseId $warehouseId,
        private ZoneTypeId $zoneTypeId,
        private ZoneCode $code,
        private NamingPolicy $namingPolicy,
    ) {
    }

    public function id(): ZoneId
    {
        return $this->id;
    }

    public function warehouseId(): WarehouseId
    {
        return $this->warehouseId;
    }

    public function zoneTypeId(): ZoneTypeId
    {
        return $this->zoneTypeId;
    }

    public function code(): ZoneCode
    {
        return $this->code;
    }

    public function namingPolicy(): NamingPolicy
    {
        return $this->namingPolicy;
    }
}
