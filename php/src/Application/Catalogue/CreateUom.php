<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Catalogue;

use Alxarafe\App\Domain\Catalogue\ValueObject\Uom;
use Alxarafe\App\Domain\Catalogue\ValueObject\UomCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\UomId;
use InvalidArgumentException;

final readonly class CreateUom
{
    public function __construct(private UomRepository $repository)
    {
    }

    public function execute(string $code, string $description): Uom
    {
        if (strlen($description) > 255) {
            throw new InvalidArgumentException('Uom description cannot exceed 255 characters.');
        }

        $uom = new Uom(UomId::generate(), new UomCode($code), $description);
        if ($this->repository->findByCode($uom->code()) !== null) {
            throw new UomConflict('Uom code already exists.');
        }

        $this->repository->save($uom);
        return $uom;
    }
}
