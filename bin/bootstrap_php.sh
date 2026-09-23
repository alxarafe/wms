#!/bin/bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
compose=(docker compose -f "$ROOT_DIR/docker-compose.yml")

echo "🔧 Bootstrapping PHP tooling..."

if ! "${compose[@]}" ps --services --filter status=running | grep -qx "php-app"; then
  echo "❌ PHP container is not running. Start it with ./bin/start.sh"
  exit 1
fi

run_php() {
  "${compose[@]}" exec -T php-app "$@"
}

# Install dependencies if needed
if ! run_php test -f vendor/autoload.php; then
  echo "📦 Installing dependencies..."
  run_php composer install --no-interaction --prefer-dist
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
  if ! run_php test -f "$t"; then
    echo "⚠ Missing tool: $t"
    MISSING=1
  fi
done

if [ "$MISSING" -eq 1 ]; then
  echo "📦 Reinstalling dev dependencies..."
  run_php composer install --no-interaction --prefer-dist
fi

echo "✔ PHP tooling ready"