<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Catalogue\ValueObject;

use InvalidArgumentException;

/**
 * Value Object representing a monetary amount with currency.
 */
final readonly class Money
{
    public function __construct(
        private float $amount,
        private string $currency,
    ) {
        if ($amount < 0.0) {
            throw new InvalidArgumentException(
                'Money amount cannot be negative. Got: ' . $amount
            );
        }

        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            throw new InvalidArgumentException(
                'Money currency must be a 3-letter ISO 4217 code. Got: ' . $currency
            );
        }
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency;
    }
}
