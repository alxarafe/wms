<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Catalogue;

use Alxarafe\App\Domain\Catalogue\Entity\ItemFamily;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeId;

interface ItemFamilyRepository
{
    public function findByCode(ItemFamilyCode $code): ?ItemFamily;

    /** @return list<ItemFamily> */
    public function findAll(): array;

    /** @param list<StorageAttributeId> $ids
     *  @return list<string>
     */
    public function existingAttributeIds(array $ids): array;

    /** Guarda familia y vínculos atómicamente; revalida y protege las referencias durante el guardado. */
    public function save(ItemFamily $family): void;
}
