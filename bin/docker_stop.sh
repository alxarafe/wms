#!/bin/bash
# ─────────────────────────────────────────────────────────────
# Hexagonal Application — Stop Development Environment
# ─────────────────────────────────────────────────────────────

set -euo pipefail

source "$(dirname "$0")/lib/bootstrap.sh"

clear

echo ""
echo -e "${BOLD}${CYAN}Hexagonal Application — Stopping containers${NC}"
echo "════════════════════════════════════════════"
echo ""

# ─────────────────────────────────────────────
# Docker Compose check
# ─────────────────────────────────────────────

if [ ! -f "$PROJECT_DIR/docker-compose.yml" ]; then
    error "❌ docker-compose.yml not found"
    exit 1
fi

# ─────────────────────────────────────────────
# Stop containers
# ─────────────────────────────────────────────

info "Stopping Docker environment..."
echo ""

if ! docker compose -f "$PROJECT_DIR/docker-compose.yml" down; then
    echo ""
    error "❌ Failed to stop containers"
    exit 1
fi

# ─────────────────────────────────────────────
# Success output
# ─────────────────────────────────────────────

echo ""
ok "✔ Containers stopped successfully"
echo ""

info "Volumes and database data preserved"
echo ""