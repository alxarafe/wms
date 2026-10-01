#!/usr/bin/env bash
set -euo pipefail

project_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
compose=(docker compose -f "$project_dir/docker-compose.yml" -f "$project_dir/docker-compose.bruno.yml")
postgres_container="$("${compose[@]}" ps -q database)"
if [[ -z "$postgres_container" ]]; then
    echo "PostgreSQL no está iniciado. Arranca primero el servicio database." >&2
    exit 1
fi

# Usar database_test que es a la que se conecta php-api-test
database_name="database_test"

# Resetear y aplicar datos maestros + semilla (002) que reset_test_database.sh trunca
"$project_dir/bin/reset_test_database.sh"
docker exec -i "$postgres_container" psql -U root -d "$database_name" -v ON_ERROR_STOP=1 \
    < "$project_dir/database/migrations/002_master_data.sql" >/dev/null
docker exec -i "$postgres_container" psql -U root -d "$database_name" -v ON_ERROR_STOP=1 \
    < "$project_dir/database/migrations/002_seed_demo_data.sql" >/dev/null

"${compose[@]}" up -d --force-recreate --no-deps php-api-test

for service_url in \
    "http://localhost:${BRUNO_PHP_PORT:-28081}/api/health"; do
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
# El estado inicial debe comprobarse antes de las entradas y salidas.
for collection in health state operations; do
    docker run --rm --network "${project_name}_default" --entrypoint bru \
        -v "$project_dir/api-tests/bruno/$collection:/bruno:ro" -w /bruno \
        "$cli_image" run --env php-docker -r
done

differences="$(docker exec -i "$postgres_container" psql -X -q -U root -d "$database_name" \
    -v ON_ERROR_STOP=1 -tA < "$project_dir/api-tests/bruno/operations/verify.sql")"
if [[ "$differences" != '0' ]]; then
    echo "Persistencia inesperada en $database_name: $differences diferencias" >&2
    exit 1
fi
echo "PHP: salud, estado inicial y operaciones verificados; persistencia esperada correcta"
