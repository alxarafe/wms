<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Catalogue;

use Alxarafe\App\Domain\Catalogue\Entity\Item;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemId;
use Alxarafe\App\Domain\Catalogue\ValueObject\Sku;
use Alxarafe\App\Domain\Catalogue\ValueObject\UomCode;
use InvalidArgumentException;

final readonly class CreateItem
{
    public function __construct(
        private ItemRepository $items,
        private ItemFamilyRepository $families,
        private UomRepository $uoms,
    ) {
    }

    public function execute(
        string $sku,
        string $name,
        string $familyCode,
        string $baseUomCode,
        bool $isBatchManaged,
        bool $isExpirable,
    ): Item {
        if (strlen($name) > 255) {
            throw new InvalidArgumentException('Item name cannot exceed 255 characters.');
        }

        $family = $this->families->findByCode(new ItemFamilyCode($familyCode));
        if ($family === null) {
            throw new InvalidArgumentException('Item family code does not exist.');
        }

        $uom = $this->uoms->findByCode(new UomCode($baseUomCode));
        if ($uom === null) {
            throw new InvalidArgumentException('Base unit of measure code does not exist.');
        }

        $item = new Item(
            ItemId::generate(),
            new Sku($sku),
            $name,
            $family->id(),
            $uom->id(),
            $isBatchManaged,
            $isExpirable,
        );

        if ($this->items->findBySku($item->sku()) !== null) {
            throw new ItemConflict('Item sku already exists.');
        }

        $this->items->save($item);
        return $item;
    }
}
