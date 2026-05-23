<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Topology\Entity;

use Alxarafe\App\Domain\Topology\ValueObject\WarehouseCode;
use Alxarafe\App\Domain\Topology\ValueObject\WarehouseId;
use InvalidArgumentException;

/**
 * Aggregate Root representing a physical warehouse.
 */
final readonly class Warehouse
{
    public function __construct(
        private WarehouseId $id,
        private WarehouseCode $code,
        private string $name,
    ) {
        if ($name === '') {
            throw new InvalidArgumentException('Warehouse name cannot be empty.');
        }
    }

    public function id(): WarehouseId
    {
        return $this->id;
    }

    public function code(): WarehouseCode
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }
}
