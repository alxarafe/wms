#!/bin/bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
PHP_DIR="$ROOT_DIR/php"

echo "🔧 Bootstrapping PHP tooling..."

cd "$PHP_DIR"

if [ ! -f composer.json ]; then
  echo "❌ composer.json not found in php/"
  exit 1
fi

# Install dependencies if needed
if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
  echo "📦 Installing dependencies..."
  composer install --no-interaction --prefer-dist
fi

# Verify required tools
TOOLS=(
  "vendor/bin/phpcs"
  "vendor/bin/phpstan"
  "vendor/bin/phpunit"
  "vendor/bin/deptrac"
)

MISSING=0

for t in "${TOOLS[@]}"; do
  if [ ! -f "$t" ]; then
    echo "⚠ Missing tool: $t"
    MISSING=1
  fi
done

if [ "$MISSING" -eq 1 ]; then
  echo "📦 Reinstalling dev dependencies..."
  composer install --no-interaction --prefer-dist
fi

echo "✔ PHP tooling ready"