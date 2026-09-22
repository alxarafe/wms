#!/usr/bin/env bash
# Dos bases exclusivas de PHP; no inicia ni modifica servicios o bases Java.
set -euo pipefail

project_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
compose=(docker compose -f "$project_dir/docker-compose.yml" -f "$project_dir/docker-compose.php-configuration.yml")
postgres_container="$("${compose[@]}" ps -q database)"
if [[ -z "$postgres_container" ]]; then
    echo 'Arranca PostgreSQL con docker compose up -d database.' >&2
    exit 1
fi
docker image inspect wms-php-app:latest >/dev/null
docker exec "$postgres_container" pg_isready -U root -d postgres >/dev/null
report_dir="$(mktemp -d /tmp/wms-php-configuration.XXXXXX)"
echo "Informes Bruno: $report_dir"

reset_databases() {
    "${compose[@]}" stop php-config-a php-config-b
    # Lista literal y cerrada: nunca se toma el destino de POSTGRES_DB ni .env.
    for test_database in wms_php_configuration_a_test wms_php_configuration_b_test; do
        docker exec "$postgres_container" dropdb -U root --if-exists --force "$test_database"
        docker exec "$postgres_container" createdb -U root "$test_database"
    done
    "${compose[@]}" up -d --no-deps php-config-a php-config-b
    for service in php-config-a php-config-b; do
        "${compose[@]}" exec -T "$service" php bin/migrate.php
        # Verifica también que una segunda invocación no vuelve a aplicar SQL.
        "${compose[@]}" exec -T "$service" php bin/migrate.php
    done
}

reset_databases
"${compose[@]}" exec -T php-config-a vendor/bin/phpunit tests/Unit tests/SmokeTest.php
for service in php-config-a php-config-b; do
    "${compose[@]}" exec -T -e PHP_CONFIGURATION_TEST=1 "$service" \
        vendor/bin/phpunit tests/Integration/Configuration
done
# Las fixtures de integración no pasan a Bruno: ambas bases se recrean vacías.
reset_databases

project_name="$(docker inspect "$postgres_container" --format '{{ index .Config.Labels "com.docker.compose.project" }}')"
collection_dir="$project_dir/api-tests/bruno/php-configuration"
cli_image="${BRUNO_CLI_IMAGE:-usebruno/cli:4.0.0}"
for suffix in a b; do
    service="php-config-$suffix"
    test_database="wms_php_configuration_${suffix}_test"
    ready=0
    for attempt in {1..30}; do
        if "${compose[@]}" exec -T "$service" curl --silent --fail http://localhost/api/health >/dev/null; then
            ready=1
            break
        fi
        sleep 1
    done
    [[ "$ready" == 1 ]] || { echo "No responde $service" >&2; exit 1; }
    docker run --rm --network "${project_name}_default" --entrypoint bru \
        -v "$collection_dir:/bruno:ro" -v "$report_dir:/reports" -w /bruno \
        "$cli_image" run bootstrap --env-var "base_url=http://$service" \
        --reporter-json "/reports/$suffix-bootstrap.json"
    docker exec -i "$postgres_container" psql -X -U root -d "$test_database" \
        -v ON_ERROR_STOP=1 -v expected_aisles=0 < "$collection_dir/verify.sql"
    docker exec -i "$postgres_container" psql -X -U root -d "$test_database" \
        -v ON_ERROR_STOP=1 < "$collection_dir/format-lock-fixture.sql"
    docker run --rm --network "${project_name}_default" --entrypoint bru \
        -v "$collection_dir:/bruno:ro" -v "$report_dir:/reports" -w /bruno \
        "$cli_image" run format-lock --env-var "base_url=http://$service" \
        --reporter-json "/reports/$suffix-format-lock.json"
    docker exec -i "$postgres_container" psql -X -U root -d "$test_database" \
        -v ON_ERROR_STOP=1 -v expected_aisles=1 < "$collection_dir/verify.sql"
done
echo "PHPUnit, Bruno y persistencia correctos en ambas bases. Informes: $report_dir"
