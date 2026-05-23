<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Catalogue\ValueObject;

use InvalidArgumentException;

/**
 * Value Object representing a Stock Keeping Unit code.
 */
final readonly class Sku
{
    public function __construct(private string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('SKU cannot be empty.');
        }

        if (strlen($value) > 50) {
            throw new InvalidArgumentException(
                'SKU must not exceed 50 characters. Got: ' . $value
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
