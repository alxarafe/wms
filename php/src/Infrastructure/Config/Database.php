<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Config;

final class Database
{
    private static ?\PDO $connection = null;

    public static function getConnection(): \PDO
    {
        if (self::$connection === null) {
            $host = $_ENV['POSTGRES_HOST'] ?? 'database';
            $port = $_ENV['POSTGRES_PORT'] ?? '5432';
            $dbname = $_ENV['POSTGRES_DB'] ?? 'database';
            $user = $_ENV['POSTGRES_USER'] ?? 'root';
            $password = $_ENV['POSTGRES_PASSWORD'] ?? 'root';

            self::$connection = new \PDO(
                sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $dbname),
                $user,
                $password,
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ],
            );
        }

        return self::$connection;
    }
}
