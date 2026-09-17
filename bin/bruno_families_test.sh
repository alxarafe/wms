#!/usr/bin/env bash
set -euo pipefail

project_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
compose=(docker compose -f "$project_dir/docker-compose.yml" -f "$project_dir/docker-compose.bruno.yml")
postgres_container="$("${compose[@]}" ps -q database)"
if [[ -z "$postgres_container" ]]; then
    echo "PostgreSQL no está iniciado. Arranca primero el servicio database." >&2
    exit 1
fi

migration="$project_dir/database/migrations/001_create_wms_schema.sql"
if [[ ! -f "$migration" ]]; then
    echo "Falta la migración $migration" >&2
    exit 1
fi

for database_name in database_bruno_php database_bruno_java; do
    if ! docker exec "$postgres_container" psql -U root -d postgres -tAc \
        "SELECT 1 FROM pg_database WHERE datname = '$database_name'" | grep -q 1; then
        docker exec "$postgres_container" createdb -U root "$database_name"
    fi
    docker exec "$postgres_container" psql -U root -d "$database_name" -v ON_ERROR_STOP=1 \
        -c 'DROP SCHEMA public CASCADE; CREATE SCHEMA public;' >/dev/null
    docker exec -i "$postgres_container" psql -U root -d "$database_name" -v ON_ERROR_STOP=1 \
        < "$migration" >/dev/null
done

"${compose[@]}" up -d --force-recreate --no-deps php-api-test java-api-test

for service_url in \
    "http://localhost:${BRUNO_PHP_PORT:-28081}/api/health" \
    "http://localhost:${BRUNO_JAVA_PORT:-28082}/api/health"; do
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
done

project_name="$(docker inspect "$postgres_container" --format '{{ index .Config.Labels "com.docker.compose.project" }}')"
cli_image="${BRUNO_CLI_IMAGE:-usebruno/cli:4.0.0}"
docker run --rm --network "${project_name}_default" --entrypoint bru \
    -v "$project_dir/api-tests/bruno/families:/bruno:ro" -w /bruno \
    "$cli_image" run --env docker

for database_name in database_bruno_php database_bruno_java; do
    differences="$(docker exec -i "$postgres_container" psql -U root -d "$database_name" \
        -v ON_ERROR_STOP=1 -tA < "$project_dir/api-tests/bruno/families/verify.sql")"
    if [[ "$differences" != '0' ]]; then
        echo "Persistencia inesperada en $database_name: $differences diferencias" >&2
        exit 1
    fi
    echo "$database_name: cinco familias y sus atributos verificados"
done
