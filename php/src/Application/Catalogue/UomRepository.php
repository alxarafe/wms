<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Catalogue;

use Alxarafe\App\Domain\Catalogue\ValueObject\Uom;
use Alxarafe\App\Domain\Catalogue\ValueObject\UomCode;

interface UomRepository
{
    public function findByCode(UomCode $code): ?Uom;

    /** @return list<Uom> */
    public function findAll(): array;

    public function save(Uom $uom): void;
}
