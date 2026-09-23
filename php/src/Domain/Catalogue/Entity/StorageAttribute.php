<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Catalogue\Entity;

use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeId;
use InvalidArgumentException;

final readonly class StorageAttribute
{
    private string $name;
    private ?string $exclusiveGroupCode;

    public function __construct(
        private StorageAttributeId $id,
        private StorageAttributeCode $code,
        string $name,
        ?string $exclusiveGroupCode = null,
    ) {
        $name = trim($name);
        $length = preg_match_all('/./us', $name);
        if ($name === '' || str_contains($name, "\0") || $length === false || $length > 255) {
            throw new InvalidArgumentException('Attribute name must contain 1-255 characters without NUL.');
        }
        if ($exclusiveGroupCode !== null) {
            $exclusiveGroupCode = strtoupper(trim($exclusiveGroupCode));
            if (preg_match('/^[A-Z][A-Z0-9_]{0,29}$/', $exclusiveGroupCode) !== 1) {
                throw new InvalidArgumentException('Exclusive group code must contain 1-30 letters, digits or underscores, starting with a letter.');
            }
        }
        $this->name = $name;
        $this->exclusiveGroupCode = $exclusiveGroupCode;
    }

    public function id(): StorageAttributeId
    {
        return $this->id;
    }

    public function code(): StorageAttributeCode
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function exclusiveGroupCode(): ?string
    {
        return $this->exclusiveGroupCode;
    }
}
