<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Catalogue\ValueObject;

/**
 * Value Object representing a Unit of Measure.
 */
final readonly class Uom
{
    public function __construct(
        private UomId $id,
        private UomCode $code,
        private string $description,
    ) {
    }

    public function id(): UomId
    {
        return $this->id;
    }

    public function code(): UomCode
    {
        return $this->code;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }
}
