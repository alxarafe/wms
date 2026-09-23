#!/bin/bash
set -euo pipefail

if ! docker compose ps --services --filter status=running | grep -qx "php-app"; then
  echo "PHP container is not running. Start it with ./bin/start.sh" >&2
  exit 1
fi

docker compose exec -T php-app vendor/bin/phpunit
