<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Catalogue;

use Alxarafe\App\Domain\Catalogue\Entity\Item;
use Alxarafe\App\Domain\Catalogue\ValueObject\Sku;

interface ItemRepository
{
    public function findBySku(Sku $sku): ?Item;

    /** @return list<ItemView> */
    public function findAll(): array;

    public function save(Item $item): void;
}
