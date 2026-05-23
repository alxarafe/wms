<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Topology\ValueObject;

use InvalidArgumentException;

/**
 * Value Object representing a zone type code (e.g. PICKING, BULK, RETURNS).
 */
final readonly class ZoneTypeCode
{
    public function __construct(private string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('ZoneTypeCode cannot be empty.');
        }

        if (preg_match('/^[A-Z][A-Z0-9_]{1,19}$/', $value) !== 1) {
            throw new InvalidArgumentException(
                'ZoneTypeCode must be uppercase alphanumeric with underscores (2-20 chars). Got: ' . $value
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
