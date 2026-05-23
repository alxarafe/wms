<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Topology\ValueObject;

use InvalidArgumentException;

/**
 * Value Object representing a unique warehouse code.
 */
final readonly class WarehouseCode
{
    public function __construct(private string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('WarehouseCode cannot be empty.');
        }

        if (strlen($value) > 10) {
            throw new InvalidArgumentException(
                'WarehouseCode must not exceed 10 characters. Got: ' . $value
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
