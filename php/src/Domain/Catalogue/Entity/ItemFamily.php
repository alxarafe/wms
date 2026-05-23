<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Catalogue\Entity;

use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyId;
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
    ) {
        if ($name === '') {
            throw new InvalidArgumentException('ItemFamily name cannot be empty.');
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
}
