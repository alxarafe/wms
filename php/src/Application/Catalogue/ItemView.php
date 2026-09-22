<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Catalogue;

use Alxarafe\App\Domain\Catalogue\Entity\Item;

/**
 * Projection of an item with the codes of its family and base UoM,
 * used to shape the HTTP responses without leaking identifiers to clients.
 */
final readonly class ItemView
{
    public function __construct(
        public Item $item,
        public string $familyCode,
        public string $baseUomCode,
    ) {
    }
}
