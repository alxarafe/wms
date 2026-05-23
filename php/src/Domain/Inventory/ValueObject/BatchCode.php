<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Inventory\ValueObject;

use InvalidArgumentException;

final readonly class BatchCode
{
    public function __construct(private string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('BatchCode cannot be empty.');
        }
        if (strlen($value) > 30) {
            throw new InvalidArgumentException(
                'BatchCode must not exceed 30 characters. Got: ' . $value
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
