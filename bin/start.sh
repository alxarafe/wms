#!/bin/bash
# ─────────────────────────────────────────────────────────────
# Hexagonal Template — Development Environment
# UX entrypoint
# ─────────────────────────────────────────────────────────────

set -euo pipefail

source "$(dirname "$0")/lib/bootstrap.sh"

clear

echo ""
echo -e "${BOLD}${CYAN}Hexagonal Template — Starting environment${NC}"
echo "════════════════════════════════════════════"
echo ""

info "Bootstrapping infrastructure..."
echo ""

bash "$SCRIPT_DIR/docker_start.sh"

# ─────────────────────────────────────────────
# UX status view
# ─────────────────────────────────────────────

echo ""
info "Running services"
echo ""

docker compose \
    -f "$PROJECT_DIR/docker-compose.yml" \
    ps --format "table {{.Name}}\t{{.Status}}\t{{.Ports}}"

# ─────────────────────────────────────────────
# UX output
# ─────────────────────────────────────────────

echo ""
ok "Environment ready"
echo ""

echo -e "${BOLD}Services:${NC}"
echo -e "  • PHP API:   ${CYAN}http://localhost:${PHP_HTTP_PORT:-8081}${NC}"
echo -e "  • Java API:  ${CYAN}http://localhost:${JAVA_HTTP_PORT:-8082}${NC}"
echo -e "  • Database:  ${CYAN}localhost:${POSTGRES_PORT:-5432}${NC}"

echo ""

echo -e "${BOLD}Next steps:${NC}"
echo -e "  • PHP tests:   ${YELLOW}./bin/php_test.sh${NC}"
echo -e "  • Java tests:  ${YELLOW}./bin/java_test.sh${NC}"
echo -e "  • Stop env:    ${YELLOW}./bin/docker_stop.sh${NC}"

echo ""