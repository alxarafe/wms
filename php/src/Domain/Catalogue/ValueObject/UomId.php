<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Catalogue\ValueObject;

use Alxarafe\App\Domain\Shared\ValueObject\UuidV7Id;

final readonly class UomId extends UuidV7Id
{
    public static function generate(): self
    {
        $bytes = random_bytes(16);
        $milliseconds = (int) floor(microtime(true) * 1000);
        for ($index = 5; $index >= 0; $index--) {
            $bytes[$index] = chr($milliseconds & 0xff);
            $milliseconds >>= 8;
        }
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x70);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return new self(sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20),
        ));
    }
}
