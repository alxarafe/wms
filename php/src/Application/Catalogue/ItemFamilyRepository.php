<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Catalogue;

use Alxarafe\App\Domain\Catalogue\Entity\ItemFamily;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeCode;

interface ItemFamilyRepository
{
    public function findByCode(ItemFamilyCode $code): ?ItemFamily;

    /** @return list<ItemFamily> */
    public function findAll(): array;

    /** @param list<StorageAttributeCode> $codes
     *  @return list<string>
     */
    public function existingAttributeCodes(array $codes): array;

    /** Guarda familia y vínculos atómicamente; revalida y protege las referencias durante el guardado. */
    public function save(ItemFamily $family): void;
}
