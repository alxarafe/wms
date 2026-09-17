<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Catalogue;

use Alxarafe\App\Domain\Catalogue\Entity\ItemFamily;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyId;
use Alxarafe\App\Domain\Rules\ValueObject\AttributeCode;
use InvalidArgumentException;

final readonly class CreateItemFamily
{
    public function __construct(private ItemFamilyRepository $repository)
    {
    }

    /** @param list<string> $attributeCodes */
    public function execute(string $code, string $name, array $attributeCodes): ItemFamily
    {
        $attributes = array_map(static fn (string $value): AttributeCode => new AttributeCode($value), $attributeCodes);
        $family = new ItemFamily(ItemFamilyId::generate(), new ItemFamilyCode($code), $name, $attributes);
        $available = $this->repository->availableFamilyAttributes($attributes);
        foreach ($attributes as $attribute) {
            if (!in_array($attribute->value(), $available, true)) {
                throw new InvalidArgumentException('Unknown FAMILY attribute: ' . $attribute->value());
            }
        }

        if ($this->repository->findByCode($family->code()) !== null) {
            throw new ItemFamilyConflict('Item family code already exists.');
        }

        $this->repository->save($family);
        return $family;
    }
}
