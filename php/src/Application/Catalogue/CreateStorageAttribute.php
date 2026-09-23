<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Catalogue;

use Alxarafe\App\Domain\Catalogue\Entity\StorageAttribute;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeId;
use Alxarafe\App\Domain\Shared\ValueObject\Uuid;

final readonly class CreateStorageAttribute
{
    public function __construct(private StorageAttributeRepository $repository)
    {
    }

    public function execute(string $code, string $name, ?string $exclusiveGroupCode = null): StorageAttribute
    {
        $attribute = new StorageAttribute(new StorageAttributeId(Uuid::generate()), new StorageAttributeCode($code), $name, $exclusiveGroupCode);
        if ($this->repository->findByCode($attribute->code()) !== null) {
            throw new StorageAttributeConflict('Storage attribute code already exists.');
        }
        $this->repository->save($attribute);
        return $attribute;
    }
}
