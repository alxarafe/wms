-- ─────────────────────────────────────────────────────────────
-- 009_fix_sku_format.sql
-- Actualiza SKUs a formato recomendado (sin espacios, con guiones)
-- Estructura: CAT-MOD-ATR-VAR
-- ─────────────────────────────────────────────────────────────

BEGIN;

-- YOGUR FRESA → FOOD-YOG-FRE-EA (Food, Yogur, Fresa, EA)
UPDATE item
SET sku = 'FOOD-YOG-FRE-EA'
WHERE id = '01a0aca9-bc00-7030-8000-000000000001';

-- PALITOS CANGREJO → FOOD-PAL-CNG-EA (Food, Palitos, Cangrejo, EA)
UPDATE item
SET sku = 'FOOD-PAL-CNG-EA'
WHERE id = '01a0aca9-bc00-7030-8000-000000000002';

-- ARROZ LARGO → FOOD-ARZ-LRG-EA (Food, Arroz, Largo, EA)
UPDATE item
SET sku = 'FOOD-ARZ-LRG-EA'
WHERE id = '01a0aca9-bc00-7030-8000-000000000003';

-- LEJIA BLANCA → CHEM-LEJ-BLA-PAL (Chem, Lejia, Blanca, PAL)
UPDATE item
SET sku = 'CHEM-LEJ-BLA-PAL'
WHERE id = '01a0aca9-bc00-7030-8000-000000000004';

-- AGUA MINERAL → FOOD-AGU-MIN-EA (Food, Agua, Mineral, EA)
UPDATE item
SET sku = 'FOOD-AGU-MIN-EA'
WHERE id = '01a0aca9-bc00-7030-8000-000000000005';

COMMIT;