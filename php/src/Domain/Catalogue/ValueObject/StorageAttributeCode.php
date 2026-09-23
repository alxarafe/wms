<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Catalogue\ValueObject;

use InvalidArgumentException;

final readonly class StorageAttributeCode
{
    private string $value;

    public function __construct(string $value)
    {
        $value = strtoupper(trim($value));
        if (preg_match('/^[A-Z][A-Z0-9_]{0,29}$/', $value) !== 1) {
            throw new InvalidArgumentException('Attribute code must contain 1-30 letters, digits or underscores, starting with a letter.');
        }
        if (in_array($value, ['IS_FOOD', 'IS_CHEMICAL', 'IS_CHILLED', 'IS_FROZEN'], true)) {
            throw new InvalidArgumentException('Legacy attribute codes are not accepted. Use FOOD, CHEMICAL, CHILLED or FROZEN.');
        }
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }
}
