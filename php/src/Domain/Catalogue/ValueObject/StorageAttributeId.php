<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Catalogue\ValueObject;

use Alxarafe\App\Domain\Shared\ValueObject\UuidV7Id;

final readonly class StorageAttributeId extends UuidV7Id
{
    public function __construct(string $value)
    {
        parent::__construct(strtolower($value));
    }
}
