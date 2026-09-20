<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Catalogue;

use Alxarafe\App\Domain\Catalogue\Entity\ItemFamily;

final readonly class ListItemFamilies
{
    public function __construct(private ItemFamilyRepository $repository)
    {
    }

    /** @return list<ItemFamily> */
    public function execute(): array
    {
        return $this->repository->findAll();
    }
}
