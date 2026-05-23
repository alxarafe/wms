<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Catalogue\ValueObject;

use InvalidArgumentException;

/**
 * Value Object representing a conversion factor between two units of measure.
 */
final readonly class ItemUomConversion
{
    public function __construct(
        private UomId $fromUomId,
        private UomId $toUomId,
        private float $factor,
    ) {
        if ($factor <= 0.0) {
            throw new InvalidArgumentException(
                'ItemUomConversion factor must be positive. Got: ' . $factor
            );
        }

        if ($fromUomId->equals($toUomId)) {
            throw new InvalidArgumentException(
                'ItemUomConversion cannot convert a UoM to itself.'
            );
        }
    }

    public function fromUomId(): UomId
    {
        return $this->fromUomId;
    }

    public function toUomId(): UomId
    {
        return $this->toUomId;
    }

    public function factor(): float
    {
        return $this->factor;
    }

    public function equals(self $other): bool
    {
        return $this->fromUomId->equals($other->fromUomId)
            && $this->toUomId->equals($other->toUomId)
            && $this->factor === $other->factor;
    }
}
