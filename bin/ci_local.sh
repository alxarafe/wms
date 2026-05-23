#!/bin/bash
set -euo pipefail

echo "=== PHP CI Pipeline ==="

# ─────────────────────────────────────────────
# Ensure Docker services are running
# ─────────────────────────────────────────────

if ! docker compose ps | grep -q "php-app"; then
    echo "❌ PHP container is not running"
    exit 1
fi

if ! docker compose ps | grep -q "java-app"; then
    echo "❌ Java container is not running"
    exit 1
fi

# ─────────────────────────────────────────────
# Ensure dependencies (PHP)
# ─────────────────────────────────────────────

echo "Bootstrapping dependencies..."
docker compose exec php-app composer install --no-interaction

# ─────────────────────────────────────────────
# PHP Code Style (PHPCS)
# ─────────────────────────────────────────────

echo "Running PHPCS..."
docker compose exec php-app vendor/bin/phpcs

# ─────────────────────────────────────────────
# PHP Static Analysis (PHPStan)
# ─────────────────────────────────────────────

echo "Running PHPStan..."
docker compose exec php-app vendor/bin/phpstan analyse

# ─────────────────────────────────────────────
# PHP Tests (PHPUnit)
# ─────────────────────────────────────────────

echo "Running PHPUnit..."
docker compose exec php-app vendor/bin/phpunit

# ─────────────────────────────────────────────
# PHP Architecture (Deptrac)
# ─────────────────────────────────────────────

echo "Running Deptrac..."
docker compose exec php-app vendor/bin/deptrac analyse

echo "=== Java CI Pipeline ==="

# ─────────────────────────────────────────────
# Java Tests (Maven + ArchUnit)
# ─────────────────────────────────────────────

echo "Running Maven tests..."
docker compose exec java-app mvn test

echo "✅ All CI checks passed"
