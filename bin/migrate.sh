#!/bin/bash
# ─────────────────────────────────────────────────────────────
# Database migrations — Plain SQL via Docker
# ─────────────────────────────────────────────────────────────

set -euo pipefail

source "$(dirname "$0")/lib/bootstrap.sh"

MIGRATIONS_DIR="$PROJECT_DIR/database/migrations"
CONTAINER="hexagonal_database"

# ─────────────────────────────────────────────
# Env
# ─────────────────────────────────────────────

export $(grep -v '^#' "$PROJECT_DIR/.env" | grep -v '^$' | xargs)

DB_NAME="${POSTGRES_DB:-database}"
DB_USER="${POSTGRES_USER:-root}"

# ─────────────────────────────────────────────
# Run migrations
# ─────────────────────────────────────────────

info "Running migrations from $MIGRATIONS_DIR..."

for file in "$MIGRATIONS_DIR"/*.sql; do
    [ -f "$file" ] || continue

    filename=$(basename "$file")
    echo ""
    info "  → $filename"

    docker exec -i "$CONTAINER" psql \
        -U "$DB_USER" \
        -d "$DB_NAME" \
        -f - < "$file" \
        -q 2>&1 | grep -v "^$" || true

    ok "  ✔ $filename applied"
done

echo ""
ok "All migrations applied"
