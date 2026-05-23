<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Rules\ValueObject;

/**
 * Value Object representing a dynamic domain attribute.
 *
 * Can be linked to Location (e.g., HAS_REFRIGERATION) or
 * ItemFamily (e.g., COLD, HAZMAT).
 */
final readonly class Attribute
{
    public function __construct(
        private AttributeId $id,
        private AttributeCode $code,
        private TargetType $targetType,
    ) {
    }

    public function id(): AttributeId
    {
        return $this->id;
    }

    public function code(): AttributeCode
    {
        return $this->code;
    }

    public function targetType(): TargetType
    {
        return $this->targetType;
    }

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }
}
