#!/usr/bin/env bash
# Colecciones compartidas, ejecutadas solo contra PHP por ahora.
set -euo pipefail
project_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
# Secuencial: catálogo y operaciones comparten database_bruno_php.
for suite in families uoms items operations; do
    echo "Bruno PHP: $suite"
    "$project_dir/bin/bruno_${suite}_test.sh"
done
"$project_dir/bin/php_configuration_test.sh"
echo "Todas las colecciones Bruno verificadas contra PHP. Java sigue aplazado."
