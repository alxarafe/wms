<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Configuration;

use InvalidArgumentException;

final class ConfigurationText
{
    public static function validate(string $value, int $maximum, string $field): void
    {
        if (trim($value) === '' || preg_match('//u', $value) !== 1 || str_contains($value, "\0")) {
            throw new InvalidArgumentException("Invalid $field.");
        }
        if (preg_match_all('/./us', $value) > $maximum) {
            throw new InvalidArgumentException("$field exceeds $maximum characters.");
        }
    }
}
