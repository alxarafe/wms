<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Catalogue\Entity;

use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyId;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeCode;
use InvalidArgumentException;

/**
 * Entity representing a product family / category.
 *
 * ItemFamily is the anchor for risk attributes (COLD, HAZMAT, etc.)
 * and compatibility rules, avoiding per-SKU redundancy.
 */
final readonly class ItemFamily
{
    public function __construct(
        private ItemFamilyId $id,
        private ItemFamilyCode $code,
        private string $name,
        /** @var list<StorageAttributeCode> */
        private array $attributes = [],
    ) {
        if (trim($name) === '') {
            throw new InvalidArgumentException('ItemFamily name cannot be empty.');
        }
        if (strlen($name) > 255) {
            throw new InvalidArgumentException('ItemFamily name cannot exceed 255 characters.');
        }
        $codes = array_map(static fn (StorageAttributeCode $attribute): string => $attribute->value(), $attributes);
        if (count(array_unique($codes)) !== count($codes)) {
            throw new InvalidArgumentException('ItemFamily attributes must be unique.');
        }
    }

    public function id(): ItemFamilyId
    {
        return $this->id;
    }

    public function code(): ItemFamilyCode
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    /** @return list<StorageAttributeCode> */
    public function attributes(): array
    {
        return $this->attributes;
    }
}
