<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Catalogue;

final readonly class ListItems
{
    public function __construct(private ItemRepository $repository)
    {
    }

    /** @return list<ItemView> */
    public function execute(): array
    {
        return $this->repository->findAll();
    }
}
