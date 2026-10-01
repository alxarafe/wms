-- ─────────────────────────────────────────────────────────────
-- 008_fix_demo_location_roles.sql
-- Corrige roles de huecos: A nivel 1 = PICKING, nivel 2 = RESERVE
-- ─────────────────────────────────────────────────────────────

BEGIN;

-- Actualiza aisle_definition: A tiene 1 nivel de picking
UPDATE aisle_definition
SET picking_max_level = 1
WHERE aisle_code = 'A' AND zone_id = '01a0aca9-bc00-7011-8000-000000000001';

-- Cambia nivel 2 de A a RESERVE (códigos ya actualizados por 006)
UPDATE location
SET role = 'RESERVE'
WHERE id = '01a0aca9-bc00-7020-8000-000000000002';

UPDATE location
SET role = 'RESERVE'
WHERE id = '01a0aca9-bc00-7020-8000-000000000004';

UPDATE location
SET role = 'RESERVE'
WHERE id = '01a0aca9-bc00-7020-8000-000000000006';

UPDATE location
SET role = 'RESERVE'
WHERE id = '01a0aca9-bc00-7020-8000-000000000008';

-- Elimina stock en ubicaciones que pasan a RESERVE (para mantener 1 HU por hueco)
DELETE FROM stock_quant
WHERE hu_id IN (
    SELECT id FROM handling_unit WHERE location_id IN (
        '01a0aca9-bc00-7020-8000-000000000002',
        '01a0aca9-bc00-7020-8000-000000000004',
        '01a0aca9-bc00-7020-8000-000000000006',
        '01a0aca9-bc00-7020-8000-000000000008'
    )
);

DELETE FROM handling_unit
WHERE location_id IN (
    '01a0aca9-bc00-7020-8000-000000000002',
    '01a0aca9-bc00-7020-8000-000000000004',
    '01a0aca9-bc00-7020-8000-000000000006',
    '01a0aca9-bc00-7020-8000-000000000008'
);

-- Añade stock en A.2.03.1 (nivel 1 RESERVE de calle B) con AGUA MINERAL
-- location 0015 = A.2.03.1 (evita duplicado en A.2.01.1 que ya tiene LEJIA BLANCA del seed)
INSERT INTO handling_unit (id, code, location_id, parent_hu_id, status) VALUES
    ('01a0aca9-bc00-7040-8000-000000000006', '340000000000000006',
     '01a0aca9-bc00-7020-8000-000000000015', NULL, 'AVAILABLE')
ON CONFLICT (id) DO NOTHING;

INSERT INTO stock_quant (id, hu_id, item_id, batch_id, quantity, unit) VALUES
    ('01a0aca9-bc00-7041-8000-000000000006', '01a0aca9-bc00-7040-8000-000000000006',
     '01a0aca9-bc00-7030-8000-000000000005', NULL, 50, 'EA')
ON CONFLICT (id) DO NOTHING;

-- Corrige HU 4 que quedó en A.2.02.2 (0013) por conflicto de ID en ejecuciones previas
-- Lo mueve a A.2.04.1 (location 0018) que está libre
UPDATE handling_unit
SET location_id = '01a0aca9-bc00-7020-8000-000000000018'
WHERE id = '01a0aca9-bc00-7040-8000-000000000004'
  AND location_id = '01a0aca9-bc00-7020-8000-000000000013';

-- Mueve su stock_quant correspondiente
UPDATE stock_quant
SET hu_id = '01a0aca9-bc00-7040-8000-000000000004'
WHERE hu_id = '01a0aca9-bc00-7040-8000-000000000004'
  AND item_id = '01a0aca9-bc00-7030-8000-000000000004';

COMMIT;