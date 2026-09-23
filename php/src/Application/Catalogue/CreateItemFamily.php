<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Catalogue;

use Alxarafe\App\Domain\Catalogue\Entity\ItemFamily;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyId;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeId;

final readonly class CreateItemFamily
{
    public function __construct(private ItemFamilyRepository $repository)
    {
    }

    /** @param list<string> $attributeIds */
    public function execute(string $code, string $name, array $attributeIds): ItemFamily
    {
        $attributes = array_map(static fn (string $value): StorageAttributeId => new StorageAttributeId($value), $attributeIds);
        $family = new ItemFamily(ItemFamilyId::generate(), new ItemFamilyCode($code), $name, $attributes);
        $available = $this->repository->existingAttributeIds($attributes);
        foreach ($attributes as $attribute) {
            if (!in_array($attribute->value(), $available, true)) {
                throw new StorageAttributeNotFound('Storage attribute not found: ' . $attribute->value());
            }
        }

        if ($this->repository->findByCode($family->code()) !== null) {
            throw new ItemFamilyConflict('Item family code already exists.');
        }

        $this->repository->save($family);
        return $family;
    }
}
