#!/usr/bin/env bash
set -euo pipefail

project_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
postgres_container="$(docker compose -f "$project_dir/docker-compose.yml" ps -q database)"
if [[ -z "$postgres_container" ]]; then
    echo "PostgreSQL no está iniciado. Arranca primero ./bin/start.sh." >&2
    exit 1
fi

if ! docker exec "$postgres_container" psql -U root -d postgres -tAc \
    "SELECT 1 FROM pg_database WHERE datname = 'database_test'" | grep -q 1; then
    echo "No existe database_test. Recrea el entorno PostgreSQL para aplicar database/mvp.sql." >&2
    exit 1
fi

docker exec -i "$postgres_container" psql -v ON_ERROR_STOP=1 -U root -d database_test <<'SQL'
DO $$
DECLARE
  relation record;
BEGIN
  FOR relation IN
    SELECT schemaname, tablename
    FROM pg_tables
    WHERE schemaname IN ('public', 'wms_review_v2')
  LOOP
    EXECUTE format('TRUNCATE TABLE %I.%I CASCADE', relation.schemaname, relation.tablename);
  END LOOP;
END $$;
SQL
