<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Inventory\ValueObject;

use InvalidArgumentException;

/**
 * Value Object representing a Serial Shipping Container Code (SSCC-18).
 *
 * Validates that the input is exactly 18 digits and complies with
 * the standard GS1 check digit calculation.
 */
final readonly class Sscc
{
    public function __construct(private string $value)
    {
        if (preg_match('/^\d{18}$/', $value) !== 1) {
            throw new InvalidArgumentException(
                'SSCC must be exactly 18 digits. Got: ' . $value
            );
        }

        if (!$this->validateCheckDigit($value)) {
            throw new InvalidArgumentException(
                'SSCC has an invalid check digit. Got: ' . $value
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

    private function validateCheckDigit(string $sscc): bool
    {
        $sum = 0;
        // The first 17 digits are used for calculation
        // Alternating weights: even indices (0, 2, 4...) get 3, odd indices get 1.
        for ($i = 0; $i < 17; $i++) {
            $digit = (int)$sscc[$i];
            $weight = ($i % 2 === 0) ? 3 : 1;
            $sum += $digit * $weight;
        }

        $remainder = $sum % 10;
        $expectedCheckDigit = ($remainder === 0) ? 0 : 10 - $remainder;
        $actualCheckDigit = (int)$sscc[17];

        return $expectedCheckDigit === $actualCheckDigit;
    }
}
