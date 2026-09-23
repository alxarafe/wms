<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Catalogue;

use Alxarafe\App\Domain\Catalogue\Entity\StorageAttribute;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeId;

final readonly class ReadStorageAttributes
{
    public function __construct(private StorageAttributeRepository $repository)
    {
    }

    public function byId(string $id): StorageAttribute
    {
        return $this->repository->find(new StorageAttributeId($id))
            ?? throw new StorageAttributeNotFound('Storage attribute not found.');
    }

    /** @return list<StorageAttribute> */
    public function all(): array
    {
        return $this->repository->findAll();
    }
}
