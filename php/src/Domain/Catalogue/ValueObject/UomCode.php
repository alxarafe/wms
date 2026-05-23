<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Catalogue\ValueObject;

use InvalidArgumentException;

/**
 * Value Object representing a unit of measure code (EA, BOX, PAL, etc.).
 */
final readonly class UomCode
{
    public function __construct(private string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('UomCode cannot be empty.');
        }

        if (preg_match('/^[A-Z][A-Z0-9]{1,9}$/', $value) !== 1) {
            throw new InvalidArgumentException(
                'UomCode must be uppercase alphanumeric (2-10 chars). Got: ' . $value
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
