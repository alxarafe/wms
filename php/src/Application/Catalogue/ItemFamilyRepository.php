<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Catalogue;

use Alxarafe\App\Domain\Catalogue\Entity\ItemFamily;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyCode;
use Alxarafe\App\Domain\Rules\ValueObject\AttributeCode;

interface ItemFamilyRepository
{
    public function findByCode(ItemFamilyCode $code): ?ItemFamily;

    /** @return list<ItemFamily> */
    public function findAll(): array;

    /** @param list<AttributeCode> $codes
     *  @return list<string>
     */
    public function availableFamilyAttributes(array $codes): array;

    public function save(ItemFamily $family): void;
}
