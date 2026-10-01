#!/usr/bin/env bash
set -euo pipefail

bootstrap_file=/docker-entrypoint-mvp.sql
for database_name in database database_test; do
  psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -d "$database_name" -f "$bootstrap_file" -q
done

psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -d database_test <<'SQL'
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
