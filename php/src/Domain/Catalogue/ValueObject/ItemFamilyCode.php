<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Catalogue\ValueObject;

use InvalidArgumentException;

/**
 * Value Object representing an item family code.
 */
final readonly class ItemFamilyCode
{
    public function __construct(private string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('ItemFamilyCode cannot be empty.');
        }

        if (strlen($value) > 20) {
            throw new InvalidArgumentException(
                'ItemFamilyCode must not exceed 20 characters. Got: ' . $value
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
