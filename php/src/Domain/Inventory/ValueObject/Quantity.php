<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Inventory\ValueObject;

use InvalidArgumentException;

final readonly class Quantity
{
    public function __construct(
        private float $value,
        private string $unit,
    ) {
        if ($value <= 0.0) {
            throw new InvalidArgumentException(
                'Quantity must be strictly positive. Got: ' . $value
            );
        }
        if ($unit === '') {
            throw new InvalidArgumentException('Quantity unit cannot be empty.');
        }
    }

    public function value(): float
    {
        return $this->value;
    }

    public function unit(): string
    {
        return $this->unit;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value
            && $this->unit === $other->unit;
    }

    public function add(self $other): self
    {
        if ($this->unit !== $other->unit) {
            throw new InvalidArgumentException(
                "Cannot add quantities with different units: {$this->unit} vs {$other->unit}"
            );
        }
        return new self($this->value + $other->value, $this->unit);
    }

    public function subtract(self $other): self
    {
        if ($this->unit !== $other->unit) {
            throw new InvalidArgumentException(
                "Cannot subtract quantities with different units: {$this->unit} vs {$other->unit}"
            );
        }
        if ($this->value < $other->value) {
            throw new InvalidArgumentException(
                "Insufficient quantity for subtraction. Has: {$this->value}, wants: {$other->value}"
            );
        }
        return new self($this->value - $other->value, $this->unit);
    }
}
