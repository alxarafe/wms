<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Catalogue;

use Alxarafe\App\Domain\Catalogue\Entity\StorageAttribute;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeId;

interface StorageAttributeRepository
{
    public function find(StorageAttributeId $id): ?StorageAttribute;
    public function findByCode(StorageAttributeCode $code): ?StorageAttribute;
    /** @return list<StorageAttribute> */
    public function findAll(): array;
    /** @throws StorageAttributeConflict */
    public function save(StorageAttribute $attribute): void;
}
