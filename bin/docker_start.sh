#!/bin/bash
# ─────────────────────────────────────────────────────────────
# Infrastructure bootstrap
# Starts required Docker services
# ─────────────────────────────────────────────────────────────

set -euo pipefail

source "$(dirname "$0")/lib/bootstrap.sh"

REQUIRED_SERVICES=(
    "php-app"
    "java-app"
    "database"
)

# ─────────────────────────────────────────────
# Docker checks
# ─────────────────────────────────────────────

if ! command -v docker >/dev/null 2>&1; then
    error "Docker is not installed"
    exit 1
fi

if ! docker info >/dev/null 2>&1; then
    error "Docker is not running"
    exit 1
fi

# ─────────────────────────────────────────────
# Environment loading
# ─────────────────────────────────────────────

if [ ! -f "$PROJECT_DIR/.env" ]; then
    if [ -f "$PROJECT_DIR/.env.example" ]; then
        cp "$PROJECT_DIR/.env.example" "$PROJECT_DIR/.env"
    else
        error ".env.example not found"
        exit 1
    fi
fi

export $(grep -v '^#' "$PROJECT_DIR/.env" | grep -v '^$' | xargs)

# ─────────────────────────────────────────────
# Detect missing services
# ─────────────────────────────────────────────

MISSING_SERVICES=()

for service in "${REQUIRED_SERVICES[@]}"; do
    if ! docker compose \
        -f "$PROJECT_DIR/docker-compose.yml" \
        ps --services --filter "status=running" \
        | grep -q "^${service}$"; then

        MISSING_SERVICES+=("$service")
    fi
done

# ─────────────────────────────────────────────
# Start containers if needed
# ─────────────────────────────────────────────

if [ ${#MISSING_SERVICES[@]} -gt 0 ]; then

    docker compose \
        -f "$PROJECT_DIR/docker-compose.yml" \
        up -d --build

fi

# ─────────────────────────────────────────────
# Final validation
# ─────────────────────────────────────────────

for service in "${REQUIRED_SERVICES[@]}"; do
    if ! docker compose \
        -f "$PROJECT_DIR/docker-compose.yml" \
        ps --services --filter "status=running" \
        | grep -q "^${service}$"; then

        error "Service not running: $service"
        exit 1
    fi
done