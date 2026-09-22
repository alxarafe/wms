<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Catalogue\Entity;

use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyId;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemId;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemUomConversion;
use Alxarafe\App\Domain\Catalogue\ValueObject\Sku;
use Alxarafe\App\Domain\Catalogue\ValueObject\UomId;
use InvalidArgumentException;

/**
 * Aggregate Root representing a product / merchandise item.
 */
final class Item
{
    /** @var ItemUomConversion[] */
    private array $uomConversions = [];

    public function __construct(
        private readonly ItemId $id,
        private readonly Sku $sku,
        private readonly string $name,
        private readonly ItemFamilyId $familyId,
        private readonly UomId $baseUomId,
        private readonly bool $isBatchManaged,
        private readonly bool $isExpirable,
    ) {
        if ($name === '') {
            throw new InvalidArgumentException('Item name cannot be empty.');
        }

        if ($isExpirable && !$isBatchManaged) {
            throw new InvalidArgumentException(
                'An expirable item must also be batch-managed.'
            );
        }
    }

    public function id(): ItemId
    {
        return $this->id;
    }

    public function sku(): Sku
    {
        return $this->sku;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function familyId(): ItemFamilyId
    {
        return $this->familyId;
    }

    public function baseUomId(): UomId
    {
        return $this->baseUomId;
    }

    public function isBatchManaged(): bool
    {
        return $this->isBatchManaged;
    }

    public function isExpirable(): bool
    {
        return $this->isExpirable;
    }

    /**
     * @return ItemUomConversion[]
     */
    public function uomConversions(): array
    {
        return $this->uomConversions;
    }

    /**
     * Adds a UoM conversion to this item.
     *
     * @throws InvalidArgumentException if a duplicate conversion exists
     */
    public function addUomConversion(ItemUomConversion $conversion): void
    {
        foreach ($this->uomConversions as $existing) {
            if (
                $existing->fromUomId()->equals($conversion->fromUomId())
                && $existing->toUomId()->equals($conversion->toUomId())
            ) {
                throw new InvalidArgumentException(
                    'Duplicate UoM conversion for this item.'
                );
            }
        }

        $this->uomConversions[] = $conversion;
    }
}
