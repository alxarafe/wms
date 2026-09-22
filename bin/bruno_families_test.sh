#!/usr/bin/env bash
set -euo pipefail

project_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
compose=(docker compose -f "$project_dir/docker-compose.yml" -f "$project_dir/docker-compose.bruno.yml")
postgres_container="$("${compose[@]}" ps -q database)"
if [[ -z "$postgres_container" ]]; then
    echo "PostgreSQL no está iniciado. Arranca primero el servicio database." >&2
    exit 1
fi

migration="$project_dir/database/migrations/004_create_wms_review_v2_schema.sql"
if [[ ! -f "$migration" ]]; then
    echo "Falta la migración $migration" >&2
    exit 1
fi

database_name="database_bruno_php"
if ! docker exec "$postgres_container" psql -U root -d postgres -tAc \
    "SELECT 1 FROM pg_database WHERE datname = '$database_name'" | grep -q 1; then
    docker exec "$postgres_container" createdb -U root "$database_name"
fi
# Solo esquema v2 limpio (004); sin datos sembrados. Java queda aparcado.
docker exec "$postgres_container" psql -U root -d "$database_name" -v ON_ERROR_STOP=1 \
    -c 'DROP SCHEMA IF EXISTS wms_review_v2 CASCADE;' >/dev/null
docker exec -i "$postgres_container" psql -U root -d "$database_name" -v ON_ERROR_STOP=1 \
    < "$migration" >/dev/null

"${compose[@]}" up -d --force-recreate --no-deps php-api-test

service_url="http://localhost:${BRUNO_PHP_PORT:-28081}/api/health"
ready=0
for attempt in {1..90}; do
    if curl --silent --fail "$service_url" >/dev/null; then
        ready=1
        break
    fi
    sleep 2
done
if [[ "$ready" -ne 1 ]]; then
    echo "La API no arrancó: $service_url" >&2
    exit 1
fi

collection_dir="$project_dir/api-tests/bruno/families"

project_name="$(docker inspect "$postgres_container" --format '{{ index .Config.Labels "com.docker.compose.project" }}')"
cli_image="${BRUNO_CLI_IMAGE:-usebruno/cli:4.0.0}"
docker run --rm --network "${project_name}_default" --entrypoint bru \
    -v "$collection_dir:/bruno:ro" -w /bruno \
    "$cli_image" run --env php-docker -r

differences="$(docker exec -i "$postgres_container" psql -q -U root -d "$database_name" \
    -v ON_ERROR_STOP=1 -tA < "$collection_dir/verify.sql")"
if [[ "$differences" != '0' ]]; then
    echo "Persistencia inesperada en $database_name: $differences diferencias" >&2
    exit 1
fi
echo "$database_name: cinco familias y sus atributos verificados"
