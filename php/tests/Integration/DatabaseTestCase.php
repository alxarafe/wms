<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    private const MIGRATIONS_PATH_ENV = 'MIGRATIONS_PATH';
    private const DEFAULT_MIGRATIONS_PATH = '/var/www/database/migrations';

    private ?\PDO $pdo = null;

    /**
     * @return list<string>
     */
    abstract protected static function getMigrationFiles(): array;

    protected function getPdo(): \PDO
    {
        return $this->pdo ??= $this->createPdo();
    }

    protected function setUp(): void
    {
        $pdo = $this->getPdo();
        $pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->pdo !== null && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::applyMigrations();
        self::truncateAllTables();
    }

    private static function applyMigrations(): void
    {
        $pdo = self::createStaticPdo();
        if ($pdo === null) {
            return;
        }

        $migrationsPath = getenv(self::MIGRATIONS_PATH_ENV) ?: self::DEFAULT_MIGRATIONS_PATH;

        foreach (static::getMigrationFiles() as $file) {
            $path = $migrationsPath . '/' . $file;
            if (file_exists($path)) {
                $sql = file_get_contents($path);
                if ($sql !== false && trim($sql) !== '') {
                    $pdo->exec($sql);
                }
            }
        }
    }

    private static function truncateAllTables(): void
    {
        $pdo = self::createStaticPdo();
        if ($pdo === null) {
            return;
        }

        $stmt = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        if ($stmt === false) {
            return;
        }

        $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if ($tables !== []) {
            $pdo->exec('SET session_replication_role = replica');
            foreach ($tables as $table) {
                $pdo->exec("TRUNCATE TABLE \"{$table}\" CASCADE");
            }
            $pdo->exec('SET session_replication_role = origin');
        }
    }

    private function createPdo(): \PDO
    {
        $pdo = self::createStaticPdo();
        if ($pdo === null) {
            self::markTestSkipped('PostgreSQL not available');
        }
        return $pdo;
    }

    private static function createStaticPdo(): ?\PDO
    {
        $host = $_ENV['POSTGRES_HOST'] ?? 'database';
        $port = $_ENV['POSTGRES_PORT'] ?? '5432';
        $dbname = $_ENV['POSTGRES_DB_TEST'] ?? $_ENV['POSTGRES_DB'] ?? 'database';
        $user = $_ENV['POSTGRES_USER'] ?? 'root';
        $password = $_ENV['POSTGRES_PASSWORD'] ?? 'root';

        try {
            return new \PDO(
                sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $dbname),
                $user,
                $password,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
            );
        } catch (\Throwable) {
            return null;
        }
    }
}
