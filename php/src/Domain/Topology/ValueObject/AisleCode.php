<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Topology\ValueObject;

use InvalidArgumentException;

/**
 * Value Object representing an aisle code within a zone.
 */
final readonly class AisleCode
{
    public function __construct(private string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('AisleCode cannot be empty.');
        }

        if (strlen($value) > 20) {
            throw new InvalidArgumentException(
                'AisleCode must not exceed 20 characters. Got: ' . $value
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
