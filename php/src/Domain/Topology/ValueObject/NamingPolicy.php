<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Topology\ValueObject;

use InvalidArgumentException;

/**
 * Value Object encapsulating the naming convention for generating location codes.
 *
 * Defines the separator and zero-padding widths for each segment
 * of a composite location code: warehouse-zone-aisle-bay-level.
 */
final readonly class NamingPolicy
{
    public function __construct(
        private string $codeSeparator,
        private int $warehousePadding,
        private int $zonePadding,
        private int $aislePadding,
        private int $bayPadding,
        private int $levelPadding,
    ) {
        if ($codeSeparator === '') {
            throw new InvalidArgumentException('NamingPolicy codeSeparator cannot be empty.');
        }

        if (strlen($codeSeparator) > 3) {
            throw new InvalidArgumentException(
                'NamingPolicy codeSeparator must not exceed 3 characters.'
            );
        }

        $paddings = [
            'warehousePadding' => $warehousePadding,
            'zonePadding' => $zonePadding,
            'aislePadding' => $aislePadding,
            'bayPadding' => $bayPadding,
            'levelPadding' => $levelPadding,
        ];

        foreach ($paddings as $name => $padding) {
            if ($padding < 1 || $padding > 10) {
                throw new InvalidArgumentException(
                    "NamingPolicy {$name} must be between 1 and 10. Got: {$padding}"
                );
            }
        }
    }

    public function codeSeparator(): string
    {
        return $this->codeSeparator;
    }

    public function warehousePadding(): int
    {
        return $this->warehousePadding;
    }

    public function zonePadding(): int
    {
        return $this->zonePadding;
    }

    public function aislePadding(): int
    {
        return $this->aislePadding;
    }

    public function bayPadding(): int
    {
        return $this->bayPadding;
    }

    public function levelPadding(): int
    {
        return $this->levelPadding;
    }

    public function equals(self $other): bool
    {
        return $this->codeSeparator === $other->codeSeparator
            && $this->warehousePadding === $other->warehousePadding
            && $this->zonePadding === $other->zonePadding
            && $this->aislePadding === $other->aislePadding
            && $this->bayPadding === $other->bayPadding
            && $this->levelPadding === $other->levelPadding;
    }
}
