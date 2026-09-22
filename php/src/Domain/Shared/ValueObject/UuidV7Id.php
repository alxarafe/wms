<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Shared\ValueObject;

use InvalidArgumentException;

/**
 * Abstract base class for typed UUID identifiers.
 *
 * Prevents primitive obsession by wrapping UUID strings
 * in strongly-typed value objects.
 */
abstract readonly class UuidV7Id
{
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[78][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    public function __construct(private string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException(
                static::class . ' cannot be empty.'
            );
        }

        if (preg_match(self::UUID_PATTERN, $value) !== 1) {
            throw new InvalidArgumentException(
                static::class . ' must be a valid UUID v7 or v8. Got: ' . $value
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return static::class === $other::class
            && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
