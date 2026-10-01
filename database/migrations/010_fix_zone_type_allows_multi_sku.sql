-- ─────────────────────────────────────────────────────────────
-- 010_fix_zone_type_allows_multi_sku.sql
-- Corrige allows_multi_sku: PICKING=true, BULK/RESERVE=false
-- ─────────────────────────────────────────────────────────────

BEGIN;

-- PICKING permite múltiples referencias (cajas sueltas compatibles)
UPDATE zone_type
SET allows_multi_sku = true
WHERE code = 'PICKING';

-- BULK (RESERVE) solo 1 referencia por hueco (palet completo)
UPDATE zone_type
SET allows_multi_sku = false
WHERE code = 'BULK';

COMMIT;