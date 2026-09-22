<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Catalogue;

use Alxarafe\App\Domain\Catalogue\ValueObject\Uom;

final readonly class ListUoms
{
    public function __construct(private UomRepository $repository)
    {
    }

    /** @return list<Uom> */
    public function execute(): array
    {
        return $this->repository->findAll();
    }
}
