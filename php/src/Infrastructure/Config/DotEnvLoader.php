<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Config;

use Dotenv\Dotenv;

final class DotEnvLoader
{
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        $dotenv = Dotenv::createImmutable($path);
        $dotenv->safeLoad();

        self::$loaded = true;
    }
}
