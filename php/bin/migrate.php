<?php

declare(strict_types=1);

// CLI únicamente: no existe una ruta HTTP para migrar o limpiar bases.
require __DIR__ . '/../vendor/autoload.php';

use Alxarafe\App\Infrastructure\Config\Database;

try {
    $pdo = Database::getConnection();
    $pdo->exec('SELECT pg_advisory_lock(72764101)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS public.php_schema_migration (
        version varchar(100) PRIMARY KEY, checksum varchar(64) NOT NULL,
        applied_at timestamptz NOT NULL DEFAULT now()
    )');
    $migrations = require __DIR__ . '/../database/migrations.php';
    foreach ($migrations as $version => $file) {
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException("Cannot read migration: $version");
        }
        $checksum = hash('sha256', $sql);
        $lookup = $pdo->prepare('SELECT checksum FROM public.php_schema_migration WHERE version = ?');
        $lookup->execute([$version]);
        $applied = $lookup->fetchColumn();
        if ($applied !== false) {
            if ($applied !== $checksum) {
                throw new RuntimeException("Applied migration changed: $version");
            }
            echo "$version: already applied\n";
            continue;
        }
        // Los SQL históricos incluyen su propia transacción. El ejecutor incluye
        // también el registro de versión en la misma transacción de cada archivo.
        $sql = preg_replace('/^\s*(BEGIN|COMMIT);\s*$/m', '', $sql);
        if ($sql === null) {
            throw new RuntimeException("Cannot parse migration: $version");
        }
        $pdo->beginTransaction();
        try {
            $pdo->exec($sql);
            $pdo->prepare('INSERT INTO public.php_schema_migration (version, checksum) VALUES (?, ?)')
                ->execute([$version, $checksum]);
            $pdo->commit();
        } catch (Throwable $error) {
            $pdo->rollBack();
            throw $error;
        }
        echo "$version: applied\n";
    }
    $pdo->exec('SELECT pg_advisory_unlock(72764101)');
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
